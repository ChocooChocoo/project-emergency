{{-- Per-row org actions via 3-dot context menu. --}}
<form id="org-archive-{{ $org->id }}" action="{{ route('admin.organizations.archive', $org) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>
<form id="org-restore-{{ $org->id }}" action="{{ route('admin.organizations.restore', $org) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>

<div class="dropdown text-center">
    <a href="#" class="btn-action dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="ti ti-dots-vertical"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-end">
        <a class="dropdown-item" href="{{ route('admin.organizations.show', $org) }}">
            <i class="ti ti-eye me-2"></i>View
        </a>
        <a class="dropdown-item" href="{{ route('admin.organizations.edit', $org) }}">
            <i class="ti ti-edit me-2"></i>Edit
        </a>

        <div class="dropdown-divider"></div>

        @if ($org->is_archived)
            <a class="dropdown-item text-primary" href="#"
               onclick="event.preventDefault(); confirmAction(
                   () => document.getElementById('org-restore-{{ $org->id }}').submit(),
                   { type:'primary', title:'Restore organization?', message:'This organization will be unarchived.', confirm:'Restore' }
               )">
                <i class="ti ti-archive-off me-2"></i>Restore
            </a>
        @else
            <a class="dropdown-item text-danger" href="#"
               onclick="event.preventDefault(); confirmAction(
                   () => document.getElementById('org-archive-{{ $org->id }}').submit(),
                   { type:'danger', title:'Archive organization?', message:'The organization will be archived and deactivated.', confirm:'Archive' }
               )">
                <i class="ti ti-archive me-2"></i>Archive
            </a>
        @endif
    </div>
</div>
