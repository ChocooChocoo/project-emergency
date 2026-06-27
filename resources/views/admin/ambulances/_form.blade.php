{{-- Shared ambulance create/edit fields. Expects $ambulance (nullable), $organizations, $drivers. --}}
@php($a = $ambulance ?? null)

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label required">Organization</label>
        <select name="organization_id" class="form-select" required>
            <option value="">— select organization —</option>
            @foreach ($organizations as $org)
                <option value="{{ $org->id }}" @selected((int) old('organization_id', $a?->organization_id) === $org->id)>{{ $org->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label required">Plate no.</label>
        <input type="text" name="plate_no" class="form-control" value="{{ old('plate_no', $a?->plate_no) }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Unit code</label>
        <input type="text" name="unit_code" class="form-control" value="{{ old('unit_code', $a?->unit_code) }}">
    </div>

    <div class="col-md-4">
        <label class="form-label">Vehicle name</label>
        <input type="text" name="vehicle_name" class="form-control" value="{{ old('vehicle_name', $a?->vehicle_name) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Tier</label>
        <select name="tier" class="form-select">
            <option value="">— unspecified —</option>
            <option value="bls" @selected(old('tier', $a?->tier) === 'bls')>BLS (Basic Life Support)</option>
            <option value="als" @selected(old('tier', $a?->tier) === 'als')>ALS (Advanced Life Support)</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">DOH credential ref.</label>
        <input type="text" name="doh_credential_ref" class="form-control" value="{{ old('doh_credential_ref', $a?->doh_credential_ref) }}">
    </div>

    <div class="col-md-3">
        <label class="form-label">Patient capacity</label>
        <input type="number" name="capacity_patients" class="form-control" min="1" max="20" value="{{ old('capacity_patients', $a?->capacity_patients ?? 1) }}">
    </div>
    <div class="col-md-5">
        <label class="form-label">Assigned driver</label>
        <select name="current_driver_user_id" class="form-select">
            <option value="">— none —</option>
            @foreach ($drivers as $driver)
                <option value="{{ $driver->id }}" @selected((int) old('current_driver_user_id', $a?->current_driver_user_id) === $driver->id)>
                    {{ trim($driver->first_name.' '.$driver->last_name) }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Operational status</label>
        <select name="status" class="form-select">
            @foreach (['available', 'on_dispatch', 'maintenance', 'out_of_service'] as $st)
                <option value="{{ $st }}" @selected(old('status', $a?->status ?? 'available') === $st)>{{ ucwords(str_replace('_', ' ', $st)) }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label class="form-label">Equipment</label>
        <div class="row">
            @foreach (\App\Models\Ambulance::EQUIPMENT as $flag => $label)
                <div class="col-6 col-md-4">
                    <label class="form-check">
                        <input type="hidden" name="{{ $flag }}" value="0">
                        <input type="checkbox" name="{{ $flag }}" value="1" class="form-check-input" @checked(old($flag, $a?->{$flag}))>
                        <span class="form-check-label">{{ $label }}</span>
                    </label>
                </div>
            @endforeach
        </div>
    </div>

    <div class="col-12">
        <label class="form-label">Equipment notes</label>
        <textarea name="equipment_notes" class="form-control" rows="2">{{ old('equipment_notes', $a?->equipment_notes) }}</textarea>
    </div>

    <div class="col-12">
        <label class="form-check">
            <input type="hidden" name="is_serviceable" value="0">
            <input type="checkbox" name="is_serviceable" value="1" class="form-check-input" @checked(old('is_serviceable', $a?->is_serviceable ?? true))>
            <span class="form-check-label">Serviceable (roadworthy)</span>
        </label>
    </div>
</div>
