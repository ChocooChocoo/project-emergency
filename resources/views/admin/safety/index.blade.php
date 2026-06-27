@extends('layout.admin.app')

@section('title', 'Anti-abuse')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">S10 — Anti-abuse</div>
            <h2 class="page-title">Device strikes &amp; flagged incidents</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            {{-- Device strikes --}}
            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">Device strikes</h3>
                    <span class="card-subtitle ms-2 text-secondary">{{ \App\Services\StrikeService::STRIKE_LIMIT }} false alarms in {{ \App\Services\StrikeService::WINDOW_DAYS }} days blocks a device</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Device</th><th>False alarms</th><th>Last flagged</th><th>State</th><th class="text-center">Actions</th></tr></thead>
                            <tbody>
                                @forelse ($devices as $d)
                                    <tr>
                                        <td class="font-monospace text-secondary">{{ \Illuminate\Support\Str::limit($d->device_uuid, 18) }}</td>
                                        <td>{{ $d->false_alarm_count }}</td>
                                        <td class="text-secondary">{{ $d->last_flagged_at?->diffForHumans() ?? '—' }}</td>
                                        <td><span class="badge bg-{{ $d->is_blocked ? 'red' : 'green' }}-lt">{{ $d->is_blocked ? 'Blocked' : 'Active' }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-action dropdown-toggle" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    @if ($d->is_blocked)
                                                        <button class="dropdown-item" onclick="document.getElementById('unblock-{{ $d->id }}').submit()"><i class="ti ti-lock-open me-2"></i>Unblock</button>
                                                        <form id="unblock-{{ $d->id }}" action="{{ route('admin.safety.unblock', $d->id) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>
                                                    @else
                                                        <button class="dropdown-item text-danger" onclick="confirmAction(() => document.getElementById('block-{{ $d->id }}').submit(), { type:'danger', title:'Block device?', confirm:'Block' })"><i class="ti ti-lock me-2"></i>Block</button>
                                                        <form id="block-{{ $d->id }}" action="{{ route('admin.safety.block', $d->id) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>
                                                    @endif
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-secondary py-4">No device strikes recorded.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($devices->hasPages())<div class="card-footer">{{ $devices->links() }}</div>@endif
            </div>

            {{-- Flagged incidents --}}
            <div class="card">
                <div class="card-header"><h3 class="card-title">Flagged incidents</h3></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Code</th><th>Status</th><th>Submitted</th></tr></thead>
                            <tbody>
                                @forelse ($flagged as $i)
                                    <tr>
                                        <td><a href="{{ route('admin.incidents.show', $i) }}">{{ $i->request_code }}</a></td>
                                        <td><span class="badge bg-orange-lt">{{ ucwords(str_replace('_', ' ', $i->status)) }}</span></td>
                                        <td class="text-secondary">{{ $i->created_at?->format('M d, Y H:i') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-secondary py-4">No flagged incidents.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
