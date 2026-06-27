{{-- Per-row ambulance actions via 3-dot context menu. --}}
<form id="amb-archive-{{ $amb->id }}" action="{{ route('admin.ambulances.archive', $amb) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>
<form id="amb-restore-{{ $amb->id }}" action="{{ route('admin.ambulances.restore', $amb) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>

<div class="dropdown text-center">
    <a href="#" class="btn-action dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="ti ti-dots-vertical"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-end">
        <a class="dropdown-item" href="{{ route('admin.ambulances.show', $amb) }}"><i class="ti ti-eye me-2"></i>View</a>
        <a class="dropdown-item" href="{{ route('admin.ambulances.edit', $amb) }}"><i class="ti ti-edit me-2"></i>Edit</a>
        <div class="dropdown-divider"></div>
        @if ($amb->is_archived)
            <a class="dropdown-item text-primary" href="#"
               onclick="event.preventDefault(); confirmAction(
                   () => document.getElementById('amb-restore-{{ $amb->id }}').submit(),
                   { type:'primary', title:'Restore ambulance?', message:'This unit will be unarchived.', confirm:'Restore' }
               )"><i class="ti ti-archive-off me-2"></i>Restore</a>
        @else
            <a class="dropdown-item text-danger" href="#"
               onclick="event.preventDefault(); confirmAction(
                   () => document.getElementById('amb-archive-{{ $amb->id }}').submit(),
                   { type:'danger', title:'Archive ambulance?', message:'The unit will be archived and marked out of service.', confirm:'Archive' }
               )"><i class="ti ti-archive me-2"></i>Archive</a>
        @endif
    </div>
</div>
