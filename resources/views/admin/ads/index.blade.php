@extends('layout.admin.app')

@section('title', 'Ad placements')

@section('content')
    <div class="page-header d-print-none">
        <div class="container-xl">
            <div class="page-pretitle">S10 — Sustainability</div>
            <h2 class="page-title">Ad placements</h2>
        </div>
    </div>

    <div class="page-body">
        <div class="container-xl">
            <div class="alert alert-info">
                <i class="ti ti-info-circle me-1"></i>
                Ads render only where <strong>emergency-safe</strong> is set, and never on the public request/tracking screens.
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table">
                            <thead><tr><th>Slot</th><th>Title</th><th>Emergency-safe</th><th>Active</th><th class="text-center">Actions</th></tr></thead>
                            <tbody>
                                @forelse ($ads as $ad)
                                    <tr>
                                        <td class="font-monospace">{{ $ad->slot }}</td>
                                        <td>{{ $ad->title ?? '—' }}</td>
                                        <td><span class="badge bg-{{ $ad->is_emergency_safe ? 'green' : 'secondary' }}-lt">{{ $ad->is_emergency_safe ? 'Yes' : 'No' }}</span></td>
                                        <td><span class="badge bg-{{ $ad->is_active ? 'green' : 'secondary' }}-lt">{{ $ad->is_active ? 'Active' : 'Off' }}</span></td>
                                        <td class="text-center">
                                            <form action="{{ route('admin.ads.toggle', $ad->id) }}" method="POST">@csrf @method('PATCH')
                                                <button class="btn btn-sm btn-outline-primary">{{ $ad->is_active ? 'Disable' : 'Enable' }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-secondary py-4">No ad placements configured.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if ($ads->hasPages())<div class="card-footer">{{ $ads->links() }}</div>@endif
            </div>
        </div>
    </div>
@endsection
