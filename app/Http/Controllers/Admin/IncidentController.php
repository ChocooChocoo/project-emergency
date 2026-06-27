<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Incident;
use App\Models\Organization;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncidentController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $type = $request->query('type');
        $status = $request->query('status');
        $orgId = $request->query('organization_id');

        $incidents = Incident::query()
            ->with('organization')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('request_code', 'like', "%{$search}%")
                ->orWhere('patient_name', 'like', "%{$search}%")))
            ->when($type, fn ($q) => $q->where('request_type', $type))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($orgId, fn ($q) => $q->where('organization_id', $orgId))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.incidents.index', [
            'incidents' => $incidents,
            'organizations' => Organization::orderBy('name')->get(['id', 'name']),
            'search' => $search, 'type' => $type, 'status' => $status, 'orgId' => $orgId,
        ]);
    }

    public function show(Incident $incident): View
    {
        $incident->load(['organization', 'user', 'guest', 'masterIncident', 'childReports', 'updates']);

        return view('admin.incidents.show', compact('incident'));
    }
}
