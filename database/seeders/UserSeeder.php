<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = 'Password123!';

        // Known, pre-verified accounts (documented in the implementation summary).
        $superAdmin = $this->makeUser([
            'account_type' => 'super_admin',
            'first_name' => 'Super', 'last_name' => 'Admin',
            'email' => 'superadmin@rescue.test',
            'account_status' => 'active',
        ], $password);

        $lgu = $this->makeUser([
            'account_type' => 'personnel',
            'first_name' => 'LGU', 'last_name' => 'Executive',
            'email' => 'lgu@rescue.test',
            'account_status' => 'active',
        ], $password);

        $this->makeUser([
            'account_type' => 'citizen',
            'first_name' => 'Sample', 'last_name' => 'Citizen',
            'email' => 'citizen@rescue.test',
            'account_status' => 'active',
        ], $password);

        // Pending personnel — used to demo the approvals module.
        $this->makeUser([
            'account_type' => 'personnel',
            'first_name' => 'Pending', 'last_name' => 'Personnel',
            'email' => 'pending@rescue.test',
            'account_status' => 'awaiting_approval',
            'is_approved' => false,
            'email_verified_at' => now(),
        ], $password);

        // Assign platform roles.
        $this->assignRole($superAdmin, 'super_admin');
        $this->assignRole($lgu, 'platform_executive');
    }

    private function makeUser(array $attrs, string $password): User
    {
        return User::updateOrCreate(
            ['email' => $attrs['email']],
            array_merge([
                'password' => $password,
                'is_active' => true,
                'is_approved' => true,
                'email_verified_at' => now(),
            ], $attrs)
        );
    }

    private function assignRole(User $user, string $roleName): void
    {
        $role = Role::where('name', $roleName)->whereNull('organization_id')->first();
        if (! $role) {
            return;
        }

        DB::table('user_roles')->updateOrInsert(
            ['user_id' => $user->id, 'role_id' => $role->id, 'organization_id' => null],
            ['assigned_at' => now(), 'created_at' => now(), 'updated_at' => now()]
        );
    }
}
