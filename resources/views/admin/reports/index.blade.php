@extends('layout.admin.app')

@section('title', 'Reports')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">S11 — LGU performance</div>
                    <h2 class="page-title">Reports</h2>
                </div>
                <div class="col-auto">
                    <button class="btn btn-outline-secondary" onclick="window.print()">
                        <i class="ti ti-printer me-2"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">

            {{-- Date-range filter (native inputs, no picker lib) --}}
            <div class="card mb-3 d-print-none">
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-2 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label">From</label>
                            <input type="date" name="from" class="form-control" value="{{ $from->toDateString() }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">To</label>
                            <input type="date" name="to" class="form-control" value="{{ $to->toDateString() }}">
                        </div>
                        <div class="col-md-3 d-flex gap-2">
                            <button class="btn btn-primary flex-fill" type="submit">Apply</button>
                            <a href="{{ route('admin.reports.index') }}" class="btn btn-outline-secondary">Last 30 days</a>
                        </div>
                    </form>
                </div>
            </div>

            <p class="text-secondary">
                Window: <strong>{{ $from->format('M d, Y') }}</strong> – <strong>{{ $to->format('M d, Y') }}</strong>
            </p>

            {{-- Headline cards --}}
            <div class="row row-deck row-cards mb-3">
                @php($cards = [
                    ['Total requests', $volume['total'], 'ti-urgent', 'bg-blue'],
                    ['Completed', $volume['completed'], 'ti-circle-check', 'bg-green'],
                    ['Dispatches', $responseKpis['dispatches'], 'ti-radar', 'bg-azure'],
                    ['Flagged abuse', $safety['flaggedIncidents'], 'ti-flag', 'bg-red'],
                ])
                @foreach ($cards as [$label, $value, $icon, $bg])
                    <div class="col-sm-6 col-lg-3">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <span class="avatar {{ $bg }} text-white me-3"><i class="ti {{ $icon }}"></i></span>
                                    <div>
                                        <div class="h1 mb-0">{{ $value }}</div>
                                        <div class="text-secondary">{{ $label }}</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="row row-cards">

                {{-- Response-time KPIs --}}
                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Response-time KPIs (minutes)</h3></div>
                        <div class="table-responsive">
                            <table class="table card-table">
                                <thead>
                                    <tr><th>Leg</th><th class="text-end">Samples</th><th class="text-end">Avg</th><th class="text-end">Median</th></tr>
                                </thead>
                                <tbody>
                                    @foreach ($responseKpis['legs'] as $leg)
                                        <tr>
                                            <td>{{ $leg['label'] }}</td>
                                            <td class="text-end text-secondary">{{ $leg['count'] }}</td>
                                            <td class="text-end">{{ $leg['avg'] ?? '—' }}</td>
                                            <td class="text-end">{{ $leg['median'] ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Outcomes --}}
                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Outcomes</h3></div>
                        <div class="card-body">
                            @php($outcomes = [
                                ['Completed', $volume['completed'], 'green'],
                                ['Resolved on scene', $volume['resolvedOnScene'], 'teal'],
                                ['Cancelled', $volume['cancelled'], 'red'],
                                ['Flagged false alarm', $volume['falseAlarm'], 'orange'],
                            ])
                            @foreach ($outcomes as [$label, $value, $color])
                                <div class="d-flex justify-content-between align-items-center py-1">
                                    <span>{{ $label }}</span>
                                    <span class="badge bg-{{ $color }}-lt">{{ $value }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Volume by status --}}
                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Requests by status</h3></div>
                        <div class="table-responsive">
                            <table class="table card-table">
                                <tbody>
                                    @forelse ($volume['byStatus'] as $status => $count)
                                        <tr>
                                            <td><span class="badge bg-secondary-lt">{{ ucwords(str_replace('_', ' ', $status)) }}</span></td>
                                            <td class="text-end">{{ $count }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-secondary py-3">No data in range.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Fleet utilization --}}
                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Busiest units</h3></div>
                        <div class="table-responsive">
                            <table class="table card-table">
                                <thead><tr><th>Unit</th><th class="text-end">Dispatches</th></tr></thead>
                                <tbody>
                                    @forelse ($fleet['busiest'] as $row)
                                        <tr>
                                            <td class="fw-bold">{{ $row['unit'] }}</td>
                                            <td class="text-end">{{ $row['dispatches'] }}</td>
                                        </tr>
                                    @empty
                                        <tr><td class="text-secondary py-3">No dispatches in range.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Fleet status snapshot --}}
                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Fleet status (now)</h3></div>
                        <div class="card-body">
                            @forelse ($fleet['statusSnapshot'] as $status => $count)
                                <div class="d-flex justify-content-between align-items-center py-1">
                                    <span class="badge bg-secondary-lt">{{ ucwords(str_replace('_', ' ', $status)) }}</span>
                                    <span>{{ $count }}</span>
                                </div>
                            @empty
                                <p class="text-secondary mb-0">No active ambulances.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                {{-- Safety & abuse --}}
                <div class="col-lg-6">
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Safety &amp; abuse</h3></div>
                        <div class="card-body">
                            @php($safetyRows = [
                                ['Flagged incidents (range)', $safety['flaggedIncidents'], 'orange'],
                                ['Blocked devices', $safety['blockedDevices'], 'red'],
                                ['Devices with strikes', $safety['devicesWithStrikes'], 'yellow'],
                                ['Total false-alarm strikes', $safety['totalStrikes'], 'secondary'],
                                ['Handoffs accepted', $safety['handoffAccepted'], 'green'],
                                ['Handoffs declined', $safety['handoffDeclined'], 'red'],
                            ])
                            @foreach ($safetyRows as [$label, $value, $color])
                                <div class="d-flex justify-content-between align-items-center py-1">
                                    <span>{{ $label }}</span>
                                    <span class="badge bg-{{ $color }}-lt">{{ $value }}</span>
                                </div>
                            @endforeach
                            <div class="d-flex justify-content-between align-items-center py-1 border-top mt-2 pt-2">
                                <span class="fw-bold">Handoff acceptance rate</span>
                                <span class="fw-bold">{{ $safety['handoffAcceptanceRate'] !== null ? $safety['handoffAcceptanceRate'].'%' : '—' }}</span>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>
    </div>
@endsection
