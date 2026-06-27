@extends('layout.admin.app')

@section('title', 'Ambulance')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Fleet / Ambulances</div>
                    <h2 class="page-title">{{ $ambulance->plate_no }} @if ($ambulance->tier)<span class="badge bg-azure-lt ms-2">{{ strtoupper($ambulance->tier) }}</span>@endif</h2>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.ambulances.index') }}" class="btn btn-link">&larr; Back</a>
                    <a href="{{ route('admin.ambulances.edit', $ambulance) }}" class="btn btn-primary"><i class="ti ti-edit me-1"></i> Edit</a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">
                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Vehicle</h3></div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-5">Organization</dt><dd class="col-7">{{ $ambulance->organization?->name ?? '—' }}</dd>
                                <dt class="col-5">Unit code</dt><dd class="col-7">{{ $ambulance->unit_code ?: '—' }}</dd>
                                <dt class="col-5">Vehicle name</dt><dd class="col-7">{{ $ambulance->vehicle_name ?: '—' }}</dd>
                                <dt class="col-5">Tier</dt><dd class="col-7">{{ $ambulance->tier ? strtoupper($ambulance->tier) : '—' }}</dd>
                                <dt class="col-5">DOH credential</dt><dd class="col-7">{{ $ambulance->doh_credential_ref ?: '—' }}</dd>
                                <dt class="col-5">Capacity</dt><dd class="col-7">{{ $ambulance->capacity_patients }}</dd>
                                <dt class="col-5">Status</dt><dd class="col-7"><span class="badge bg-blue-lt">{{ ucwords(str_replace('_', ' ', $ambulance->status)) }}</span></dd>
                                <dt class="col-5">Serviceable</dt><dd class="col-7"><span class="badge {{ $ambulance->is_serviceable ? 'bg-green-lt' : 'bg-red-lt' }}">{{ $ambulance->is_serviceable ? 'Yes' : 'No' }}</span></dd>
                                <dt class="col-5">Driver</dt><dd class="col-7">{{ $ambulance->currentDriver?->full_name ?? '—' }}</dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card mt-3">
                        <div class="card-header"><h3 class="card-title">Equipment</h3></div>
                        <div class="card-body">
                            @foreach (\App\Models\Ambulance::EQUIPMENT as $flag => $label)
                                <span class="badge {{ $ambulance->{$flag} ? 'bg-green-lt' : 'bg-secondary-lt' }} me-1 mb-1">
                                    <i class="ti {{ $ambulance->{$flag} ? 'ti-check' : 'ti-x' }} me-1"></i>{{ $label }}
                                </span>
                            @endforeach
                            @if ($ambulance->equipment_notes)
                                <p class="text-secondary mt-2 mb-0">{{ $ambulance->equipment_notes }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    {{-- Fuel logs --}}
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Fuel logs</h3>
                            <div class="card-actions"><a href="#" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#add-fuel"><i class="ti ti-plus me-1"></i>Add</a></div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table">
                                <thead><tr><th>Date</th><th>Liters</th><th>Total cost</th><th>Type</th></tr></thead>
                                <tbody>
                                    @forelse ($fuelLogs as $log)
                                        <tr>
                                            <td>{{ $log->log_date?->format('Y-m-d') }}</td>
                                            <td>{{ $log->liters }}</td>
                                            <td>{{ $log->total_cost ? '₱'.number_format($log->total_cost, 2) : '—' }}</td>
                                            <td class="text-secondary">{{ ucfirst($log->fuel_type) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-secondary py-3">No fuel logs.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    {{-- Maintenance logs --}}
                    <div class="card mt-3">
                        <div class="card-header">
                            <h3 class="card-title">Maintenance logs</h3>
                            <div class="card-actions"><a href="#" class="btn btn-sm" data-bs-toggle="modal" data-bs-target="#add-maint"><i class="ti ti-plus me-1"></i>Add</a></div>
                        </div>
                        <div class="card-body p-0">
                            <table class="table">
                                <thead><tr><th>Type</th><th>Description</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($maintenanceLogs as $log)
                                        <tr>
                                            <td>{{ ucwords(str_replace('_', ' ', $log->maintenance_type)) }}</td>
                                            <td class="text-secondary">{{ \Illuminate\Support\Str::limit($log->description, 50) }}</td>
                                            <td><span class="badge {{ $log->status === 'completed' ? 'bg-green-lt' : ($log->status === 'cancelled' ? 'bg-red-lt' : 'bg-yellow-lt') }}">{{ ucwords(str_replace('_', ' ', $log->status)) }}</span></td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="text-center text-secondary py-3">No maintenance logs.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Add fuel modal --}}
    <div class="modal" id="add-fuel" tabindex="-1">
        <div class="modal-dialog" role="document">
            <form action="{{ route('admin.ambulances.fuel-logs.store', $ambulance) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Add fuel log</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Date</label><input type="date" name="log_date" class="form-control" value="{{ now()->toDateString() }}" required></div>
                        <div class="col-6"><label class="form-label">Liters</label><input type="number" step="0.01" name="liters" class="form-control" required></div>
                        <div class="col-6"><label class="form-label">Total cost</label><input type="number" step="0.01" name="total_cost" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Fuel type</label>
                            <select name="fuel_type" class="form-select"><option value="diesel">Diesel</option><option value="gasoline">Gasoline</option><option value="premium">Premium</option><option value="other">Other</option></select>
                        </div>
                        <div class="col-12"><label class="form-label">Remarks</label><input type="text" name="remarks" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>

    {{-- Add maintenance modal --}}
    <div class="modal" id="add-maint" tabindex="-1">
        <div class="modal-dialog" role="document">
            <form action="{{ route('admin.ambulances.maintenance-logs.store', $ambulance) }}" method="POST" class="modal-content">
                @csrf
                <div class="modal-header"><h5 class="modal-title">Add maintenance log</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-6"><label class="form-label">Type</label>
                            <select name="maintenance_type" class="form-select">
                                @foreach (['preventive','corrective','emergency','inspection','tire','oil_change','brake','battery','other'] as $t)
                                    <option value="{{ $t }}">{{ ucwords(str_replace('_', ' ', $t)) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-6"><label class="form-label">Status</label>
                            <select name="status" class="form-select"><option value="scheduled">Scheduled</option><option value="in_progress">In progress</option><option value="completed">Completed</option><option value="cancelled">Cancelled</option></select>
                        </div>
                        <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2" required></textarea></div>
                        <div class="col-6"><label class="form-label">Performed by</label><input type="text" name="performed_by" class="form-control"></div>
                        <div class="col-6"><label class="form-label">Cost</label><input type="number" step="0.01" name="cost" class="form-control"></div>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-link" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save</button></div>
            </form>
        </div>
    </div>
@endsection
