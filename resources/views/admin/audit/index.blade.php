@extends('layout.admin.app')

@section('title', 'Audit Log')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">Oversight</div>
            <h2 class="page-title">Audit &amp; System Log</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Activity trail</h3>
                    <div class="ms-auto">
                        <form method="GET" class="d-flex align-items-center gap-2">
                            <select name="action" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All events</option>
                                @foreach ($actions as $a)
                                    <option value="{{ $a }}" @selected(request('action') === $a)>{{ $a }}</option>
                                @endforeach
                            </select>
                            <input type="date" name="date" value="{{ request('date') }}" class="form-control form-control-sm" onchange="this.form.submit()">
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="table-audit" class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" data-sort="sort-actor">Actor</button></th>
                                    <th><button class="table-sort" data-sort="sort-event">Event</button></th>
                                    <th>Target</th>
                                    <th><button class="table-sort" data-sort="sort-when">When</button></th>
                                    <th>Detail</th>
                                </tr>
                            </thead>
                            <tbody class="table-tbody">
                                @forelse ($logs as $log)
                                    <tr>
                                        <td class="sort-actor">
                                            {{ $log->actor_name ?: 'System' }}
                                            @if ($log->role)<span class="badge bg-secondary-lt ms-1">{{ ucwords(str_replace('_', ' ', $log->role)) }}</span>@endif
                                        </td>
                                        <td class="sort-event"><span class="badge bg-blue-lt">{{ $log->action }}</span></td>
                                        <td class="text-secondary">
                                            @if ($log->model_type){{ class_basename($log->model_type) }} #{{ $log->model_id }}@else—@endif
                                        </td>
                                        <td class="sort-when text-secondary">{{ \Illuminate\Support\Carbon::parse($log->created_at)->diffForHumans() }}</td>
                                        <td>
                                            @if ($log->new_values)
                                                <details>
                                                    <summary class="text-secondary" style="cursor:pointer">View</summary>
                                                    <pre class="mb-0 mt-1 small">{{ json_encode(json_decode($log->new_values), JSON_PRETTY_PRINT) }}</pre>
                                                </details>
                                            @else
                                                <span class="text-secondary">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-secondary py-4">No audit entries.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('tabler/libs/list.js/dist/list.min.js') }}" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            new List('table-audit', {
                sortClass: 'table-sort',
                listClass: 'table-tbody',
                valueNames: ['sort-actor', 'sort-event', 'sort-when'],
            });
        });
    </script>
@endpush
