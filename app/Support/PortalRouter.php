<?php

namespace App\Support;

use App\Models\User;

class PortalRouter
{
    /** First match wins: console users (super admin + LGU) -> dashboard; citizens -> request intake. */
    public static function homeRouteFor(User $user): string
    {
        return $user->isSuperAdmin() || $user->hasPermission('access-admin')
            ? 'dashboard'
            : 'request.create';
    }
}
