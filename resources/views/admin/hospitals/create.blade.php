@extends('layout.admin.app')

@section('title', 'New hospital')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">Hospitals</div>
            <h2 class="page-title">New hospital</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <form action="{{ route('admin.hospitals.store') }}" method="POST">
                @csrf
                <div class="card">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6"><label class="form-label required">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
                            <div class="col-md-3"><label class="form-label">Facility type</label><input name="facility_type" class="form-control" value="{{ old('facility_type', 'hospital') }}"></div>
                            <div class="col-md-3"><label class="form-label">Phone</label><input name="phone" class="form-control" value="{{ old('phone') }}"></div>
                            <div class="col-md-4"><label class="form-label">City</label><input name="city" class="form-control" value="{{ old('city', 'Dasmariñas') }}"></div>
                            <div class="col-md-4"><label class="form-label">Province</label><input name="province" class="form-control" value="{{ old('province', 'Cavite') }}"></div>
                            <div class="col-md-4">
                                <label class="form-label">Capacity</label>
                                <select name="capacity_status" class="form-select">
                                    @foreach (['available','limited','full','unknown'] as $c)<option value="{{ $c }}" @selected(old('capacity_status', 'available') === $c)>{{ ucfirst($c) }}</option>@endforeach
                                </select>
                            </div>
                            <div class="col-12"><label class="form-label">Address</label><input name="address" class="form-control" value="{{ old('address') }}"></div>
                            <div class="col-md-3"><label class="form-label">Latitude</label><input name="lat" type="number" step="any" class="form-control" value="{{ old('lat') }}"></div>
                            <div class="col-md-3"><label class="form-label">Longitude</label><input name="lng" type="number" step="any" class="form-control" value="{{ old('lng') }}"></div>
                            <div class="col-md-3"><label class="form-label">Available beds</label><input name="available_beds" type="number" class="form-control" value="{{ old('available_beds') }}"></div>
                            <div class="col-md-3 d-flex align-items-end">
                                <label class="form-check form-switch"><input class="form-check-input" type="checkbox" name="is_er_open" value="1" checked><span class="form-check-label">ER open</span></label>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer d-flex">
                        <a href="{{ route('admin.hospitals.index') }}" class="btn btn-link">Cancel</a>
                        <button class="btn btn-primary ms-auto" type="submit">Create hospital</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
