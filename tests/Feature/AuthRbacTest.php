<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\EmailOtp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthRbacTest extends TestCase
{
    use RefreshDatabase;

    /** Register -> OTP issue -> verify activates a citizen and logs them in. */
    public function test_register_and_otp_verify_activates_citizen(): void
    {
        $this->post('/register', [
            'first_name' => 'Jane', 'last_name' => 'Doe',
            'email' => 'jane@test.local',
            'password' => 'Password123!', 'password_confirmation' => 'Password123!',
            'terms' => '1',
        ])->assertRedirect(route('verify-email.show'));

        $user = User::where('email', 'jane@test.local')->firstOrFail();
        $this->assertSame('pending_otp', $user->account_status);

        // Issue a code directly (the registration one isn't returned to the test),
        // then drive the verify endpoint.
        // Citizens land on the public intake, not the console dashboard (PortalRouter).
        $code = EmailOtp::issue($user);
        $this->withSession(['pending_user_id' => $user->id])
            ->post('/verify-email', ['code' => $code])
            ->assertRedirect(route('request.create'));

        $user->refresh();
        $this->assertSame('active', $user->account_status);
        $this->assertNotNull($user->email_verified_at);
    }

    public function test_bad_otp_is_rejected(): void
    {
        $user = User::factory()->create(['account_status' => 'pending_otp', 'email_verified_at' => null]);
        EmailOtp::issue($user);

        $this->assertFalse(EmailOtp::verify($user, 'wrong0'));
        $user->refresh();
        $this->assertNull($user->email_verified_at);
    }

    /** Permission resolution: access derives only from roles' perms ∪ direct grants — no wildcard. */
    public function test_permission_resolution(): void
    {
        $perm = Permission::create(['code' => 'manage-users', 'name' => 'Manage users', 'module' => 'users']);
        $role = Role::create(['name' => 'platform_executive', 'scope' => 'platform']);
        $role->permissions()->attach($perm->id);

        $lgu = User::factory()->create(['account_type' => 'personnel']);
        DB::table('user_roles')->insert([
            'user_id' => $lgu->id, 'role_id' => $role->id, 'assigned_at' => now(),
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $lgu->load('roles.permissions', 'directPermissions');

        $this->assertTrue($lgu->hasPermission('manage-users'));
        $this->assertFalse($lgu->hasPermission('nonexistent-perm'));

        // super_admin no longer wildcards: with no roles attached it resolves nothing.
        $super = User::factory()->create(['account_type' => 'super_admin']);
        $super->load('roles.permissions', 'directPermissions');
        $this->assertFalse($super->hasPermission('anything-at-all'));
    }
}
