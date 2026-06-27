<?php

namespace Tests;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * A personnel actor holding exactly the given permission codes, via an ad-hoc
     * platform role. Used by module tests now that super_admin is oversight-only and
     * no longer wildcards into operational routes; field roles own these perms later.
     */
    protected function actorWith(array $codes): User
    {
        $user = User::factory()->create(['account_type' => 'personnel']);

        $role = Role::create(['name' => 'test_'.uniqid(), 'scope' => 'platform', 'is_active' => true]);
        $role->permissions()->sync(Permission::whereIn('code', $codes)->pluck('id'));

        DB::table('user_roles')->insert([
            'user_id' => $user->id, 'role_id' => $role->id, 'assigned_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        return $user->load('roles.permissions', 'directPermissions');
    }
}
