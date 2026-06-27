@extends('layout.citizen.app')

@section('title', 'Medical Info')

@section('content')
    <div class="page-header mb-4">
        <h2 class="page-title">Medical Info</h2>
        <div class="text-secondary">Shared with responders during an emergency.</div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <form method="POST" action="{{ route('citizen.medical.update') }}">
                    @csrf
                    @method('PUT')
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Blood type</label>
                                <input type="text" name="blood_type" class="form-control @error('blood_type') is-invalid @enderror"
                                       value="{{ old('blood_type', $medical['blood_type'] ?? '') }}" placeholder="e.g. O+">
                                @error('blood_type') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Allergies</label>
                                <textarea name="allergies" rows="2" class="form-control @error('allergies') is-invalid @enderror">{{ old('allergies', $medical['allergies'] ?? '') }}</textarea>
                                @error('allergies') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Medical conditions</label>
                                <textarea name="conditions" rows="2" class="form-control @error('conditions') is-invalid @enderror">{{ old('conditions', $medical['conditions'] ?? '') }}</textarea>
                                @error('conditions') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                            <div class="col-12">
                                <label class="form-label">Other notes</label>
                                <textarea name="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $medical['notes'] ?? '') }}</textarea>
                                @error('notes') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>
                    </div>
                    <div class="card-footer text-end">
                        <button type="submit" class="btn btn-primary">Save medical info</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
