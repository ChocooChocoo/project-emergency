<?php

namespace Tests\Feature;

use App\Models\Ambulance;
use App\Models\DispatchAssignment;
use App\Models\Incident;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DriverTrackingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
    }

    private function superAdmin(): User
    {
        return User::where('email', 'superadmin@rescue.test')->firstOrFail();
    }

    private function assignment(): DispatchAssignment
    {
        $org = Organization::create([
            'name' => 'Drv Org '.uniqid(), 'org_type' => 'partner',
            'organization_status' => 'active', 'is_active' => true, 'is_approved' => true,
        ]);
        $amb = Ambulance::create([
            'organization_id' => $org->id, 'plate_no' => 'DRV-'.uniqid(),
            'status' => 'dispatched', 'is_serviceable' => true,
        ]);
        $incident = Incident::create([
            'request_code' => 'REQ-'.strtoupper(uniqid()), 'request_type' => 'one_tap',
            'status' => 'dispatched', 'severity' => 2, 'pickup_address' => 'X', 'pickup_lat' => 14.3, 'pickup_lng' => 120.9,
        ]);

        return DispatchAssignment::create([
            'incident_id' => $incident->id, 'organization_id' => $org->id,
            'ambulance_id' => $amb->id, 'driver_user_id' => $this->superAdmin()->id,
            'status' => 'assigned', 'assigned_at' => now(),
        ]);
    }

    public function test_duty_toggle(): void
    {
        $this->actingAs($this->superAdmin())
            ->patch(route('admin.driver.duty.update'), ['status' => 'on_duty'])
            ->assertRedirect();

        $this->assertDatabaseHas('driver_duty_states', [
            'driver_user_id' => $this->superAdmin()->id, 'status' => 'on_duty',
        ]);
    }

    public function test_advance_walks_status_machine(): void
    {
        $a = $this->assignment();

        $this->actingAs($this->superAdmin())->patch(route('admin.driver.advance', $a))->assertRedirect();
        $this->assertSame('accepted', $a->fresh()->status);

        $this->actingAs($this->superAdmin())->patch(route('admin.driver.advance', $a));
        $this->assertSame('en_route', $a->fresh()->status);
    }

    public function test_advance_to_completed_frees_unit(): void
    {
        $a = $this->assignment();
        foreach (DispatchAssignment::FLOW as $_) {
            $this->actingAs($this->superAdmin())->patch(route('admin.driver.advance', $a));
        }
        $this->assertSame('completed', $a->fresh()->status);
        $this->assertSame('available', $a->ambulance->fresh()->status);
        $this->assertSame('completed', $a->incident->fresh()->status);
    }

    public function test_push_location_writes_row(): void
    {
        $a = $this->assignment();

        $this->actingAs($this->superAdmin())
            ->postJson(route('admin.driver.location', $a), ['lat' => 14.31, 'lng' => 120.92])
            ->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('ambulance_locations', ['dispatch_assignment_id' => $a->id]);
    }

    public function test_public_status_json_returns_assignment(): void
    {
        $a = $this->assignment();

        $this->getJson(route('request.status', $a->incident->request_code))
            ->assertOk()
            ->assertJsonPath('assignment.plate_no', $a->ambulance->plate_no);
    }
}
