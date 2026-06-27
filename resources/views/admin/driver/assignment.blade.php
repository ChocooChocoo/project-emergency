@extends('layout.admin.app')

@section('title', 'Assignment — ' . $assignment->incident?->request_code)

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">Driver assignment</div>
                    <h2 class="page-title">{{ $assignment->incident?->request_code }}</h2>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.driver.duty') }}" class="btn btn-link">&larr; Back</a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="row row-cards">

                <div class="col-lg-5">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Destination</h3></div>
                        <div class="card-body">
                            <dl class="row">
                                <dt class="col-5">Unit</dt><dd class="col-7">{{ $assignment->ambulance?->plate_no }}</dd>
                                <dt class="col-5">Status</dt>
                                <dd class="col-7"><span class="badge bg-blue-lt">{{ ucwords(str_replace('_', ' ', $assignment->status)) }}</span></dd>
                                <dt class="col-5">Address</dt><dd class="col-7">{{ $assignment->incident?->pickup_address }}</dd>
                                <dt class="col-5">Coordinates</dt>
                                <dd class="col-7">{{ $assignment->incident?->pickup_lat }}, {{ $assignment->incident?->pickup_lng }}</dd>
                            </dl>
                            @if ($assignment->incident?->pickup_lat)
                                <a class="btn btn-outline-primary w-100" href="geo:{{ $assignment->incident->pickup_lat }},{{ $assignment->incident->pickup_lng }}">
                                    <i class="ti ti-map-pin me-1"></i>Open in maps
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    {{-- Status stepper --}}
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Advance status</h3></div>
                        <div class="card-body">
                            <ul class="steps steps-vertical">
                                @foreach (\App\Models\DispatchAssignment::FLOW as $step)
                                    @php($done = array_search($step, \App\Models\DispatchAssignment::FLOW, true) <= array_search($assignment->status, \App\Models\DispatchAssignment::FLOW, true))
                                    <li class="step-item {{ $done ? 'active' : '' }}">{{ ucwords(str_replace('_', ' ', $step)) }}</li>
                                @endforeach
                            </ul>

                            @if ($next)
                                <form action="{{ route('admin.driver.advance', $assignment) }}" method="POST" class="mt-3">
                                    @csrf @method('PATCH')
                                    <button class="btn btn-primary w-100" type="submit">
                                        <i class="ti ti-arrow-right me-1"></i>Advance to {{ ucwords(str_replace('_', ' ', $next)) }}
                                    </button>
                                </form>
                            @else
                                <div class="alert alert-success mt-3 mb-0">Assignment complete.</div>
                            @endif

                            {{-- Demo location push (polling tracking) --}}
                            <button id="push-loc" class="btn btn-outline-secondary w-100 mt-2" type="button">
                                <i class="ti ti-broadcast me-1"></i>Push my GPS location
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('push-loc')?.addEventListener('click', () => {
            if (!navigator.geolocation) return feedback.error('Unsupported', 'Geolocation is not available.');
            navigator.geolocation.getCurrentPosition(async (pos) => {
                const r = await fetch('{{ route('admin.driver.location', $assignment) }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: JSON.stringify({ lat: pos.coords.latitude, lng: pos.coords.longitude }),
                });
                r.ok ? feedback.success('Sent', 'Location pushed to tracking.') : feedback.error('Failed', 'Could not push location.');
            }, () => feedback.error('Denied', 'Location permission denied.'));
        });
    </script>
@endpush
