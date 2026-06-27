@extends('layout.admin.app')

@section('title', 'Hospitals')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="row align-items-center">
                <div class="col">
                    <div class="page-pretitle">S9 — Hospitals</div>
                    <h2 class="page-title">Hospitals</h2>
                </div>
                <div class="col-auto">
                    <a href="{{ route('admin.hospitals.create') }}" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Add hospital</a>
                </div>
            </div>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="card mb-3">
                <div class="card-body">
                    <form method="GET" class="row g-2">
                        <div class="col-md-4"><input name="q" class="form-control" placeholder="Hospital name" value="{{ $search }}"></div>
                        <div class="col-md-2"><button class="btn btn-primary w-100">Search</button></div>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div id="table-hospitals" class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th><button class="table-sort" data-sort="sort-name">Name</button></th>
                                    <th><button class="table-sort" data-sort="sort-city">City</button></th>
                                    <th><button class="table-sort" data-sort="sort-cap">Capacity</button></th>
                                    <th>ER</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="table-tbody">
                                @forelse ($hospitals as $h)
                                    <tr>
                                        <td class="sort-name"><a href="{{ route('admin.hospitals.show', $h) }}" class="fw-bold">{{ $h->name }}</a></td>
                                        <td class="sort-city text-secondary">{{ $h->city }}</td>
                                        <td class="sort-cap"><span class="badge bg-{{ $h->capacity_status === 'full' ? 'red' : ($h->capacity_status === 'limited' ? 'yellow' : 'green') }}-lt">{{ ucfirst($h->capacity_status) }}</span></td>
                                        <td><span class="badge bg-{{ $h->is_er_open ? 'green' : 'red' }}-lt">{{ $h->is_er_open ? 'Open' : 'Closed' }}</span></td>
                                        <td class="text-center">
                                            <div class="dropdown">
                                                <button class="btn btn-action dropdown-toggle" data-bs-toggle="dropdown"><i class="ti ti-dots-vertical"></i></button>
                                                <div class="dropdown-menu dropdown-menu-end">
                                                    <a class="dropdown-item" href="{{ route('admin.hospitals.show', $h) }}"><i class="ti ti-eye me-2"></i>View</a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-secondary py-4">No hospitals registered.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($hospitals->hasPages())
                    <div class="card-footer d-flex">{{ $hospitals->links() }}</div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script src="{{ asset('tabler/libs/list.js/dist/list.min.js') }}"></script>
    <script>new List('table-hospitals', { sortClass:'table-sort', listClass:'table-tbody', valueNames:['sort-name','sort-city','sort-cap'] });</script>
@endpush
