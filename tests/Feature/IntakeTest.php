<?php

namespace Tests\Feature;

use App\Models\GuestSession;
use App\Models\Incident;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class IntakeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, PlanSeeder::class, UserSeeder::class]);
    }

    private function basePayload(array $overrides = []): array
    {
        return array_merge([
            'request_type' => 'one_tap',
            'pickup_lat' => '14.5995',
            'pickup_lng' => '120.9842',
            'pickup_address' => 'Rizal Park, Manila',
        ], $overrides);
    }

    public function test_guest_submit_creates_incident_and_guest_session(): void
    {
        $this->post(route('request.store'), $this->basePayload())
            ->assertRedirect();

        $this->assertDatabaseCount('incidents', 1);
        $this->assertDatabaseCount('guest_sessions', 1);

        $incident = Incident::first();
        $this->assertNotNull($incident->guest_id);
        $this->assertNull($incident->user_id);
        $this->assertStringStartsWith('REQ-', $incident->request_code);
    }

    public function test_guest_quota_blocks_over_limit(): void
    {
        // Create an exhausted session.
        $session = GuestSession::create([
            'guest_key' => (string) Str::uuid(),
            'requests_limit' => 3,
            'requests_used' => 3,
            'is_active' => true,
        ]);

        $this->withCookie('guest_key', $session->guest_key)
            ->from(route('request.create'))
            ->post(route('request.store'), $this->basePayload())
            ->assertRedirect(route('request.create'))
            ->assertSessionHas('error');

        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_authed_citizen_submit_sets_user_id_not_guest(): void
    {
        $citizen = User::factory()->create(['account_type' => 'citizen', 'is_active' => true]);

        $this->actingAs($citizen)
            ->post(route('request.store'), $this->basePayload())
            ->assertRedirect();

        $incident = Incident::first();
        $this->assertSame($citizen->id, $incident->user_id);
        $this->assertNull($incident->guest_id);
        $this->assertDatabaseCount('guest_sessions', 0);
    }

    public function test_nearby_reports_group_under_same_master(): void
    {
        // First report — no master yet.
        $this->post(route('request.store'), $this->basePayload())->assertRedirect();
        $first = Incident::orderBy('id')->first();
        $this->assertNull($first->master_incident_id);

        // Second report ~50m away (same lat/lng for simplicity → 0m) → should group.
        $this->post(route('request.store'), $this->basePayload([
            'pickup_lat' => '14.5995', 'pickup_lng' => '120.9842',
        ]))->assertRedirect();

        $second = Incident::orderByDesc('id')->first();
        $this->assertSame($first->id, $second->master_incident_id);
    }

    public function test_admin_incident_list_requires_view_incidents_permission(): void
    {
        $citizen = User::factory()->create(['account_type' => 'citizen', 'is_active' => true]);
        $this->actingAs($citizen)->get(route('admin.incidents.index'))->assertForbidden();
    }

    public function test_super_admin_can_view_incident_list(): void
    {
        $superAdmin = User::where('email', 'superadmin@rescue.test')->firstOrFail();
        $this->actingAs($superAdmin)->get(route('admin.incidents.index'))->assertOk();
    }
}
