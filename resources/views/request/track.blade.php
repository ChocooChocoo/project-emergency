@extends('layout.auth.app')

@section('title', 'Track Request')

@push('styles')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont/dist/tabler-icons.min.css"/>
@endpush

@section('logo')
    <span class="fw-bold fs-2">{{ config('app.name') }}</span>
@endsection

@section('content')
    <div class="card card-md" data-status-url="{{ route('request.status', $incident->request_code) }}">
        <div class="card-body text-center">
            <i class="ti ti-clipboard-check text-green" style="font-size: 3rem;"></i>
            <h2 class="h2 mt-2">Request received</h2>
            <p class="text-secondary">Your reference number is</p>
            <div class="display-6 fw-bold mb-3">{{ $incident->request_code }}</div>

            <dl class="row text-start">
                <dt class="col-5">Status</dt>
                <dd class="col-7"><span id="trk-status" class="badge bg-yellow-lt">{{ ucwords(str_replace('_', ' ', $incident->status)) }}</span></dd>
                <dt class="col-5">Type</dt>
                <dd class="col-7">{{ ucwords(str_replace('_', ' ', $incident->request_type)) }}</dd>
                <dt class="col-5">Pickup</dt>
                <dd class="col-7">{{ $incident->pickup_address }}</dd>
                <dt class="col-5">Submitted</dt>
                <dd class="col-7">{{ $incident->created_at?->diffForHumans() }}</dd>
            </dl>

            {{-- Live assignment block (populated by polling once dispatched) --}}
            <div id="trk-assignment" class="alert alert-success mt-3 mb-0 text-start d-none">
                <div class="d-flex">
                    <i class="ti ti-ambulance me-2 mt-1"></i>
                    <div class="flex-fill">
                        <strong>Unit <span id="trk-plate"></span></strong> <span id="trk-tier" class="badge bg-purple-lt ms-1"></span>
                        <div class="text-secondary small">Status: <span id="trk-unit-status"></span></div>
                        <div class="text-secondary small">Crew: <span id="trk-driver"></span></div>
                        <a id="trk-call" href="#" class="btn btn-sm btn-success mt-2 d-none"><i class="ti ti-phone me-1"></i>Call driver</a>
                    </div>
                </div>
            </div>

            <div id="trk-waiting" class="alert alert-info mt-3 mb-0">
                <i class="ti ti-info-circle me-1"></i>
                Live tracking (ETA, plate, crew) will appear here once a unit is dispatched.
            </div>

            {{-- Cancellation (held pending field verification — never a hard cancel) --}}
            @if (! in_array($incident->status, ['completed', 'resolved_on_scene']))
                <form id="trk-cancel" action="{{ route('request.cancel', $incident->request_code) }}" method="POST" class="mt-3">
                    @csrf @method('PATCH')
                    <button type="submit" class="btn btn-link text-danger btn-sm">I no longer need help</button>
                </form>
            @endif
        </div>
    </div>

    <div class="text-center text-secondary mt-3">
        <a href="{{ route('request.create') }}">Submit another request</a>
    </div>
@endsection

@push('scripts')
    <script>
        // ponytail: poll every 10s. Reverb broadcasting is the documented upgrade path.
        const card = document.querySelector('[data-status-url]');
        const url = card.dataset.statusUrl;

        async function poll() {
            try {
                const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                if (!r.ok) return;
                const d = await r.json();
                const badge = document.getElementById('trk-status');
                badge.textContent = d.status_label;

                const a = d.assignment;
                if (a) {
                    document.getElementById('trk-waiting').classList.add('d-none');
                    document.getElementById('trk-assignment').classList.remove('d-none');
                    document.getElementById('trk-plate').textContent = a.plate_no ?? '—';
                    document.getElementById('trk-tier').textContent = a.tier ?? '';
                    document.getElementById('trk-unit-status').textContent = a.unit_status ?? '—';
                    document.getElementById('trk-driver').textContent = a.driver_name ?? '—';
                    const call = document.getElementById('trk-call');
                    if (a.driver_phone) { call.href = 'tel:' + a.driver_phone; call.classList.remove('d-none'); }
                }
            } catch (e) { /* transient — next tick retries */ }
        }
        poll();
        setInterval(poll, 10000);
    </script>
@endpush
