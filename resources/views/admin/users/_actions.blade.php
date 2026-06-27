{{-- Per-row actions via 3-dot context menu. Destructive ops confirm via feedback modal. --}}

{{-- Hidden forms for POST/PATCH actions --}}
<form id="active-{{ $user->id }}"  action="{{ route('admin.users.active',  $user) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>
<form id="archive-{{ $user->id }}" action="{{ route('admin.users.archive', $user) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>
<form id="restore-{{ $user->id }}" action="{{ route('admin.users.restore', $user) }}" method="POST" class="d-none">@csrf @method('PATCH')</form>

<div class="dropdown text-center">
    <a href="#" class="btn-action dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <i class="ti ti-dots-vertical"></i>
    </a>
    <div class="dropdown-menu dropdown-menu-end">
        <a class="dropdown-item" href="{{ route('admin.users.show', $user) }}">
            <i class="ti ti-eye me-2"></i>View
        </a>

        <div class="dropdown-divider"></div>

        @if ($user->is_active)
            <a class="dropdown-item text-warning" href="#"
               onclick="event.preventDefault(); confirmAction(
                   () => document.getElementById('active-{{ $user->id }}').submit(),
                   { type:'warning', title:'Deactivate user?', message:'This user will no longer be able to log in.', confirm:'Deactivate' }
               )">
                <i class="ti ti-user-off me-2"></i>Deactivate
            </a>
        @else
            <a class="dropdown-item text-success" href="#"
               onclick="event.preventDefault(); confirmAction(
                   () => document.getElementById('active-{{ $user->id }}').submit(),
                   { type:'success', title:'Activate user?', message:'This user will regain access.', confirm:'Activate' }
               )">
                <i class="ti ti-user-check me-2"></i>Activate
            </a>
        @endif

        @if ($user->is_archived)
            <a class="dropdown-item text-primary" href="#"
               onclick="event.preventDefault(); confirmAction(
                   () => document.getElementById('restore-{{ $user->id }}').submit(),
                   { type:'primary', title:'Restore user?', message:'This user will be unarchived.', confirm:'Restore' }
               )">
                <i class="ti ti-archive-off me-2"></i>Restore
            </a>
        @else
            <a class="dropdown-item text-danger" href="#"
               onclick="event.preventDefault(); confirmAction(
                   () => document.getElementById('archive-{{ $user->id }}').submit(),
                   { type:'danger', title:'Archive user?', message:'The account will be archived and deactivated.', confirm:'Archive' }
               )">
                <i class="ti ti-archive me-2"></i>Archive
            </a>
        @endif
    </div>
</div>
