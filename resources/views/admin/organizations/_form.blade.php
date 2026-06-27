{{-- Shared org create/edit fields. Expects $organization (nullable), $plans, $admins. --}}
@php($o = $organization ?? null)
@php($subPlanId = old('plan_id', $o?->subscription?->plan_id))

@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="row g-3">
    <div class="col-md-8">
        <label class="form-label required">Name</label>
        <input type="text" name="name" class="form-control" value="{{ old('name', $o?->name) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label required">Type</label>
        <select name="org_type" class="form-select" required>
            @foreach (['lgu' => 'LGU (Government)', 'partner' => 'Partner / Private', 'ngo' => 'NGO', 'hospital' => 'Hospital-based'] as $val => $label)
                <option value="{{ $val }}" @selected(old('org_type', $o?->org_type) === $val)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Acronym</label>
        <input type="text" name="org_acronym" class="form-control" value="{{ old('org_acronym', $o?->org_acronym) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Code</label>
        <input type="text" name="code" class="form-control" value="{{ old('code', $o?->code) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label required">Subscription plan</label>
        <select name="plan_id" class="form-select" required>
            <option value="">— select plan —</option>
            @foreach ($plans as $plan)
                <option value="{{ $plan->id }}" @selected((int) $subPlanId === $plan->id)>{{ $plan->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Email</label>
        <input type="email" name="email" class="form-control" value="{{ old('email', $o?->email) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Phone</label>
        <input type="text" name="phone" class="form-control" value="{{ old('phone', $o?->phone) }}">
    </div>
    <div class="col-md-3">
        <label class="form-label">Dispatch hotline</label>
        <input type="text" name="dispatch_hotline_ops" class="form-control" value="{{ old('dispatch_hotline_ops', $o?->dispatch_hotline_ops) }}">
    </div>

    <div class="col-12">
        <label class="form-label">Address</label>
        <textarea name="address" class="form-control" rows="2">{{ old('address', $o?->address) }}</textarea>
    </div>

    <div class="col-md-4">
        <label class="form-label">Service city</label>
        <input type="text" name="service_city" class="form-control" value="{{ old('service_city', $o?->service_city ?? 'Dasmariñas') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Service type</label>
        <input type="text" name="service_type" class="form-control" value="{{ old('service_type', $o?->service_type) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">Admin contact (user)</label>
        <select name="admin_user_id" class="form-select">
            <option value="">— none —</option>
            @foreach ($admins as $admin)
                <option value="{{ $admin->id }}" @selected((int) old('admin_user_id', $o?->admin_user_id) === $admin->id)>
                    {{ trim($admin->first_name.' '.$admin->last_name) }} ({{ $admin->email }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label class="form-label">Covered barangays</label>
        <textarea name="covered_barangays_json" class="form-control" rows="2" placeholder="Comma-separated list of barangays">{{ old('covered_barangays_json', $o?->covered_barangays_json) }}</textarea>
    </div>

    <div class="col-12">
        <label class="form-check">
            <input type="hidden" name="is_24_7" value="0">
            <input type="checkbox" name="is_24_7" value="1" class="form-check-input" @checked(old('is_24_7', $o?->is_24_7))>
            <span class="form-check-label">Operates 24/7</span>
        </label>
    </div>
</div>
