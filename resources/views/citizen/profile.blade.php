@extends('layout.citizen.app')

@section('title', 'Profile')

@section('content')
    <div class="page-header mb-4">
        <h2 class="page-title">My Profile</h2>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <form method="POST" action="{{ route('citizen.profile.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label required">First name</label>
                                <input type="text" name="first_name" class="form-control @error('first_name') is-invalid @enderror"
                                       value="{{ old('first_name', $user->first_name) }}">
                                @error('first_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Middle name</label>
                                <input type="text" name="middle_name" class="form-control @error('middle_name') is-invalid @enderror"
                                       value="{{ old('middle_name', $user->middle_name) }}">
                                @error('middle_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Last name</label>
                                <input type="text" name="last_name" class="form-control @error('last_name') is-invalid @enderror"
                                       value="{{ old('last_name', $user->last_name) }}">
                                @error('last_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Suffix</label>
                                <input type="text" name="suffix" class="form-control @error('suffix') is-invalid @enderror"
                                       value="{{ old('suffix', $user->suffix) }}">
                                @error('suffix') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label required">Phone</label>
                                <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror"
                                       value="{{ old('phone', $user->phone) }}">
                                @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Alternate phone</label>
                                <input type="text" name="alt_phone" class="form-control @error('alt_phone') is-invalid @enderror"
                                       value="{{ old('alt_phone', $user->alt_phone) }}">
                                @error('alt_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="{{ $user->email }}" disabled>
                                <small class="text-secondary">Email cannot be changed here.</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
