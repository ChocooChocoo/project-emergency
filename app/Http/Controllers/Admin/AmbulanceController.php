<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Fleet\StoreAmbulanceRequest;
use App\Http\Requests\Fleet\UpdateAmbulanceRequest;
use App\Models\Ambulance;
use App\Models\Organization;
use App\Models\User;
use App\Services\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AmbulanceController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $orgId = $request->query('organization_id');
        $tier = $request->query('tier');
        $status = $request->query('status');

        $ambulances = Ambulance::query()
            ->with('organization')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('plate_no', 'like', "%{$search}%")
                ->orWhere('unit_code', 'like', "%{$search}%")
                ->orWhere('vehicle_name', 'like', "%{$search}%")))
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->when($tier, fn ($q) => $q->where('tier', $tier))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.ambulances.index', [
            'ambulances' => $ambulances,
            'organizations' => Organization::orderBy('name')->get(['id', 'name']),
            'search' => $search, 'orgId' => $orgId, 'tier' => $tier, 'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.ambulances.create', $this->formOptions());
    }

    public function store(StoreAmbulanceRequest $request): RedirectResponse
    {
        $data = $this->normalize($request->validated());

        $org = Organization::with('subscription.plan')->findOrFail($data['organization_id']);
        if ($over = $this->planCapExceeded($org)) {
            return back()->withInput()->with('error', $over);
        }

        $ambulance = Ambulance::create($data);
        AuditLog::record('ambulance.created', Ambulance::class, $ambulance->id);

        return redirect()->route('admin.ambulances.show', $ambulance)
            ->with('status', "Ambulance {$ambulance->plate_no} registered.");
    }

    public function show(Ambulance $ambulance): View
    {
        $ambulance->load('organization', 'currentDriver');

        return view('admin.ambulances.show', [
            'ambulance' => $ambulance,
            'fuelLogs' => $ambulance->fuelLogs()->orderByDesc('log_date')->limit(10)->get(),
            'maintenanceLogs' => $ambulance->maintenanceLogs()->orderByDesc('id')->limit(10)->get(),
        ]);
    }

    public function edit(Ambulance $ambulance): View
    {
        return view('admin.ambulances.edit', array_merge($this->formOptions(), ['ambulance' => $ambulance]));
    }

    public function update(UpdateAmbulanceRequest $request, Ambulance $ambulance): RedirectResponse
    {
        $ambulance->update($this->normalize($request->validated()));
        AuditLog::record('ambulance.updated', Ambulance::class, $ambulance->id);

        return redirect()->route('admin.ambulances.show', $ambulance)->with('status', 'Ambulance updated.');
    }

    public function archive(Request $request, Ambulance $ambulance): RedirectResponse
    {
        $request->validate(['archive_reason' => ['nullable', 'string', 'max:500']]);
        $ambulance->update([
            'is_archived' => true,
            'archived_at' => now(),
            'archived_by' => $request->user()->id,
            'archive_reason' => $request->input('archive_reason'),
            'is_serviceable' => false,
        ]);
        AuditLog::record('ambulance.archived', Ambulance::class, $ambulance->id);

        return back()->with('status', 'Ambulance archived.');
    }

    public function restore(Ambulance $ambulance): RedirectResponse
    {
        $ambulance->update([
            'is_archived' => false, 'archived_at' => null, 'archived_by' => null, 'archive_reason' => null,
        ]);
        AuditLog::record('ambulance.restored', Ambulance::class, $ambulance->id);

        return back()->with('status', 'Ambulance restored.');
    }

    public function storeFuelLog(Request $request, Ambulance $ambulance): RedirectResponse
    {
        $data = $request->validate([
            'log_date' => ['required', 'date'],
            'liters' => ['required', 'numeric', 'min:0'],
            'cost_per_liter' => ['nullable', 'numeric', 'min:0'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'fuel_type' => ['required', 'in:diesel,gasoline,premium,other'],
            'station' => ['nullable', 'string', 'max:150'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $ambulance->fuelLogs()->create($data + ['created_by' => $request->user()->id]);
        AuditLog::record('ambulance.fuel_logged', Ambulance::class, $ambulance->id);

        return back()->with('status', 'Fuel log added.');
    }

    public function storeMaintenanceLog(Request $request, Ambulance $ambulance): RedirectResponse
    {
        $data = $request->validate([
            'maintenance_type' => ['required', 'in:preventive,corrective,emergency,inspection,tire,oil_change,brake,battery,other'],
            'description' => ['required', 'string', 'max:1000'],
            'performed_by' => ['nullable', 'string', 'max:150'],
            'cost' => ['nullable', 'numeric', 'min:0'],
            'odometer_km' => ['nullable', 'integer', 'min:0'],
            'scheduled_date' => ['nullable', 'date'],
            'performed_date' => ['nullable', 'date'],
            'status' => ['required', 'in:scheduled,in_progress,completed,cancelled'],
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $ambulance->maintenanceLogs()->create($data + ['created_by' => $request->user()->id]);
        AuditLog::record('ambulance.maintenance_logged', Ambulance::class, $ambulance->id);

        return back()->with('status', 'Maintenance log added.');
    }

    /** Equipment/serviceable checkboxes arrive as "0"/"1"/absent — normalize to real bools. */
    private function normalize(array $data): array
    {
        foreach (array_keys(Ambulance::EQUIPMENT) as $flag) {
            $data[$flag] = (bool) ($data[$flag] ?? false);
        }
        $data['is_serviceable'] = (bool) ($data['is_serviceable'] ?? false);

        return $data;
    }

    /** Returns an error message if the org's plan ambulance cap is already met, else null. */
    private function planCapExceeded(Organization $org): ?string
    {
        $plan = $org->subscription?->plan;
        if (! $plan || $plan->is_unlimited || $plan->max_ambulances === null) {
            return null;
        }

        $current = $org->ambulances()->where('is_archived', false)->count();
        if ($current >= $plan->max_ambulances) {
            return "Plan limit reached: {$org->name} may register at most {$plan->max_ambulances} ambulances.";
        }

        return null;
    }

    private function formOptions(): array
    {
        return [
            'organizations' => Organization::where('is_archived', false)->orderBy('name')->get(['id', 'name']),
            'drivers' => User::whereIn('account_type', ['driver', 'personnel'])
                ->orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']),
        ];
    }
}
