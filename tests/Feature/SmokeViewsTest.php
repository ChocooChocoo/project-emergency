<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SmokeViewsTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_auth_pages_render(): void
    {
        $this->get('/login')->assertOk()->assertSee('Sign in');
        $this->get('/register')->assertOk()->assertSee('Create');
        $this->get('/forgot-password')->assertOk()->assertSee('Forgot');
        $this->get('/reset-password?email=a@b.c')->assertOk()->assertSee('new password');
    }

    public function test_admin_pages_render(): void
    {
        $this->seed([RolePermissionSeeder::class, PlanSeeder::class, UserSeeder::class]);

        // Oversight pages render for the super_admin (oversight-only role).
        $sa = User::where('email', 'superadmin@rescue.test')->firstOrFail();
        $this->actingAs($sa)->get('/dashboard')->assertOk()->assertSee('Dashboard');
        $this->actingAs($sa)->get('/admin/users')->assertOk()->assertSee('User Management');
        $this->actingAs($sa)->get('/admin/approvals')->assertOk()->assertSee('Approvals');
        $this->actingAs($sa)->get('/admin/org-approvals')->assertOk()->assertSee('Approvals');
        $this->actingAs($sa)->get('/admin/archive')->assertOk()->assertSee('Archive Registry');
        $this->actingAs($sa)->get('/admin/audit')->assertOk()->assertSee('Audit');
        $this->actingAs($sa)->get('/admin/incidents')->assertOk()->assertSee('Incidents');

        // Operational pages render for an actor holding every permission (field roles, later).
        $ops = $this->actorWith([
            'manage-organizations', 'manage-fleet', 'dispatch-incidents',
            'drive-unit', 'manage-hospitals', 'manage-safety',
        ]);
        $this->actingAs($ops)->get('/admin/organizations')->assertOk()->assertSee('Organizations');
        $this->actingAs($ops)->get('/admin/ambulances')->assertOk()->assertSee('Ambulances');
        $this->actingAs($ops)->get('/admin/dispatch')->assertOk()->assertSee('Dispatch');
        $this->actingAs($ops)->get('/admin/driver/duty')->assertOk()->assertSee('Duty');
        $this->actingAs($ops)->get('/admin/hospitals')->assertOk()->assertSee('Hospitals');
        $this->actingAs($ops)->get('/admin/safety')->assertOk()->assertSee('strikes');
        $this->actingAs($ops)->get('/admin/ads')->assertOk()->assertSee('Ad placements');

        $this->get('/request')->assertOk()->assertSee('Emergency Request');
    }

    public function test_citizen_is_forbidden_from_admin(): void
    {
        $cit = User::factory()->create(['account_type' => 'citizen']);
        $this->actingAs($cit)->get('/admin/users')->assertForbidden();
    }
}
