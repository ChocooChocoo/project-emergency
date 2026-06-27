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

    public function test_admin_pages_render_for_super_admin(): void
    {
        $this->seed([RolePermissionSeeder::class, PlanSeeder::class, UserSeeder::class]);
        $sa = User::where('email', 'superadmin@rescue.test')->firstOrFail();

        $this->actingAs($sa)->get('/dashboard')->assertOk()->assertSee('Dashboard');
        $this->actingAs($sa)->get('/admin/users')->assertOk()->assertSee('User Management');
        $this->actingAs($sa)->get('/admin/approvals')->assertOk()->assertSee('Approvals');
        $this->actingAs($sa)->get('/admin/organizations')->assertOk()->assertSee('Organizations');
        $this->actingAs($sa)->get('/admin/org-approvals')->assertOk()->assertSee('Approvals');
        $this->actingAs($sa)->get('/admin/ambulances')->assertOk()->assertSee('Ambulances');
        $this->actingAs($sa)->get('/admin/incidents')->assertOk()->assertSee('Incidents');
        $this->actingAs($sa)->get('/admin/dispatch')->assertOk()->assertSee('Dispatch');
        $this->actingAs($sa)->get('/admin/driver/duty')->assertOk()->assertSee('Duty');
        $this->actingAs($sa)->get('/admin/hospitals')->assertOk()->assertSee('Hospitals');
        $this->actingAs($sa)->get('/admin/safety')->assertOk()->assertSee('strikes');
        $this->actingAs($sa)->get('/admin/ads')->assertOk()->assertSee('Ad placements');
        $this->get('/request')->assertOk()->assertSee('Emergency Request');
    }

    public function test_citizen_is_forbidden_from_admin(): void
    {
        $cit = User::factory()->create(['account_type' => 'citizen']);
        $this->actingAs($cit)->get('/admin/users')->assertForbidden();
    }
}
