@extends('layout.auth.app')

@section('title', 'Request an Ambulance')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont/dist/tabler-icons.min.css"/>
@endpush

@section('logo')
    <span class="fw-bold fs-2">{{ config('app.name') }}</span>
@endsection

@section('content')
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-1">Emergency Request</h2>
            <p class="text-secondary text-center mb-4">Tap the button to send your location, or add details first.</p>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger"><ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>
            @endif

            <form action="{{ route('request.store') }}" method="POST" id="intake-form">
                @csrf
                <input type="hidden" name="request_type" id="request_type" value="one_tap">
                <input type="hidden" name="pickup_lat" id="pickup_lat" value="{{ old('pickup_lat') }}">
                <input type="hidden" name="pickup_lng" id="pickup_lng" value="{{ old('pickup_lng') }}">

                <div class="mb-3">
                    <label class="form-label required">Pickup address</label>
                    <input type="text" name="pickup_address" class="form-control" value="{{ old('pickup_address') }}" placeholder="Street, barangay, landmark" required>
                    <div class="form-hint" id="geo-hint">Location not captured yet — tap “Use my location”.</div>
                </div>

                <button type="button" class="btn btn-outline-primary w-100 mb-3" id="geo-btn">
                    <i class="ti ti-current-location me-1"></i> Use my location
                </button>

                {{-- Detailed fields (collapsed by default) --}}
                <div class="accordion mb-3" id="detail-accordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#detail-body">
                                Add details (optional)
                            </button>
                        </h2>
                        <div id="detail-body" class="accordion-collapse collapse" data-bs-parent="#detail-accordion">
                            <div class="accordion-body">
                                <div class="mb-2">
                                    <label class="form-label">Patient name</label>
                                    <input type="text" name="patient_name" class="form-control" value="{{ old('patient_name') }}">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Contact number</label>
                                    <input type="text" name="contact_number" class="form-control" value="{{ old('contact_number') }}">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Nature of emergency</label>
                                    <input type="text" name="incident_type" class="form-control" value="{{ old('incident_type') }}" placeholder="e.g. vehicular accident, cardiac">
                                </div>
                                <div class="mb-2">
                                    <label class="form-label">Details</label>
                                    <textarea name="request_summary" class="form-control" rows="2">{{ old('request_summary') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-danger btn-lg w-100">
                    <i class="ti ti-urgent me-1"></i> Send emergency request
                </button>
            </form>
        </div>
    </div>

    <div class="text-center text-secondary mt-3">
        Have an account? <a href="{{ route('login') }}">Sign in</a> for full tracking.
    </div>
@endsection

@push('scripts')
    <script>
        // Capture GPS into hidden fields; opening "details" upgrades the request type.
        document.getElementById('geo-btn').addEventListener('click', function () {
            if (!navigator.geolocation) {
                window.feedback?.error('Unavailable', 'Geolocation is not supported on this device.');
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    document.getElementById('pickup_lat').value = pos.coords.latitude.toFixed(7);
                    document.getElementById('pickup_lng').value = pos.coords.longitude.toFixed(7);
                    document.getElementById('geo-hint').textContent =
                        'Location captured: ' + pos.coords.latitude.toFixed(5) + ', ' + pos.coords.longitude.toFixed(5);
                },
                () => window.feedback?.warning('Permission needed', 'Allow location access, or type your address manually.')
            );
        });

        document.querySelector('[data-bs-target="#detail-body"]').addEventListener('click', function () {
            document.getElementById('request_type').value = 'detailed';
        });
    </script>
@endpush
