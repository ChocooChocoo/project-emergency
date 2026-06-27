@extends('layout.admin.app')

@section('title', 'Archive Registry')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">Oversight</div>
            <h2 class="page-title">Archive Registry</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            {{-- table_name -> existing module restore route. Only these three have one. --}}
            @php($restoreRoutes = [
                'users' => 'admin.users.restore',
                'organizations' => 'admin.organizations.restore',
                'ambulances' => 'admin.ambulances.restore',
            ])

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Archived records</h3>
                    <div class="ms-auto">
                        <form method="GET" class="d-flex align-items-center gap-2">
                            <select name="table" class="form-select form-select-sm" onchange="this.form.submit()">
                                <option value="">All types</option>
                                @foreach ($tables as $t)
                                    <option value="{{ $t }}" @selected(request('table') === $t)>{{ ucfirst($t) }}</option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div id="table-archive" class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" data-sort="sort-type">Type</button></th>
                                    <th><button class="table-sort" data-sort="sort-item">Item</button></th>
                                    <th><button class="table-sort" data-sort="sort-by">Archived by</button></th>
                                    <th>Reason</th>
                                    <th><button class="table-sort" data-sort="sort-when">When</button></th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-tbody">
                                @forelse ($logs as $log)
                                    @php($restoreRoute = $restoreRoutes[$log->table_name] ?? null)
                                    <tr>
                                        <td class="sort-type">{{ ucfirst($log->table_name) }}</td>
                                        <td class="sort-item text-secondary">#{{ $log->record_id }}</td>
                                        <td class="sort-by">{{ $log->archived_by_name ?: '—' }}</td>
                                        <td class="text-secondary">{{ $log->archive_reason ?: '—' }}</td>
                                        <td class="sort-when text-secondary">{{ \Illuminate\Support\Carbon::parse($log->archived_at)->diffForHumans() }}</td>
                                        <td class="text-center">
                                            @if ($restoreRoute)
                                                <form id="restore-{{ $log->id }}" action="{{ route($restoreRoute, $log->record_id) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>
                                                <div class="dropdown text-center">
                                                    <a href="#" class="btn-action dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </a>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <a class="dropdown-item text-primary" href="#"
                                                           onclick="event.preventDefault(); confirmAction(
                                                               () => document.getElementById('restore-{{ $log->id }}').submit(),
                                                               { type:'primary', title:'Restore record?', message:'This {{ \Illuminate\Support\Str::singular($log->table_name) }} will be unarchived.', confirm:'Restore' }
                                                           )">
                                                            <i class="ti ti-archive-off me-2"></i>Restore
                                                        </a>
                                                    </div>
                                                </div>
                                            @else
                                                <span class="text-secondary">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-secondary py-4">No archived records.</td></tr>
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
            new List('table-archive', {
                sortClass: 'table-sort',
                listClass: 'table-tbody',
                valueNames: ['sort-type', 'sort-item', 'sort-by', 'sort-when'],
            });
        });
    </script>
@endpush
