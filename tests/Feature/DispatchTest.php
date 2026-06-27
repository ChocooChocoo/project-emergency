<?php

namespace Tests\Feature;

use App\Models\Ambulance;
use App\Models\DispatchAssignment;
use App\Models\Incident;
use App\Models\Organization;
use App\Models\User;
use App\Services\DssService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DispatchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
    }

    private function superAdmin(): User
    {
        // Dispatcher-equivalent: super_admin is oversight-only now; field roles own this perm.
        return $this->actorWith(['dispatch-incidents']);
    }

    private function activeOrg(): Organization
    {
        return Organization::create([
            'name' => 'Disp Org '.uniqid(), 'org_type' => 'partner',
            'organization_status' => 'active', 'is_active' => true, 'is_approved' => true,
        ]);
    }

    private function availableAmbulance(Organization $org): Ambulance
    {
        return Ambulance::create([
            'organization_id' => $org->id, 'plate_no' => 'AMB-'.uniqid(),
            'tier' => 'als', 'status' => 'available', 'is_serviceable' => true,
            'last_lat' => 14.33, 'last_lng' => 120.93,
        ]);
    }

    private function pendingIncident(): Incident
    {
        return Incident::create([
            'request_code' => 'REQ-'.strtoupper(uniqid()),
            'request_type' => 'one_tap', 'status' => 'pending', 'severity' => 1,
            'pickup_lat' => 14.331, 'pickup_lng' => 120.931, 'pickup_address' => 'Test St',
        ]);
    }

    public function test_dss_ranks_available_units(): void
    {
        $org = $this->activeOrg();
        $this->availableAmbulance($org);
        $incident = $this->pendingIncident();

        $ranked = DssService::rank($incident);
        $this->assertCount(1, $ranked);
        $this->assertSame(1, $ranked->first()['dss_rank']);
        $this->assertNotNull($ranked->first()['distance_km']);
    }

    public function test_assign_creates_assignment_and_flips_statuses(): void
    {
        $org = $this->activeOrg();
        $amb = $this->availableAmbulance($org);
        $incident = $this->pendingIncident();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.dispatch.store', $incident), [
                'ambulance_id' => $amb->id,
                'driver_user_id' => $this->superAdmin()->id,
            ])->assertRedirect();

        $this->assertSame('dispatched', $incident->fresh()->status);
        $this->assertSame('dispatched', $amb->fresh()->status);
        $this->assertSame($org->id, $incident->fresh()->organization_id);
        $this->assertDatabaseHas('dispatch_assignments', [
            'incident_id' => $incident->id, 'ambulance_id' => $amb->id, 'organization_id' => $org->id,
        ]);
    }

    public function test_assignment_org_follows_ambulance_org(): void
    {
        // R7 — org is taken from the ambulance, never drifts.
        $org = $this->activeOrg();
        $amb = $this->availableAmbulance($org);
        $incident = $this->pendingIncident();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.dispatch.store', $incident), [
                'ambulance_id' => $amb->id, 'driver_user_id' => $this->superAdmin()->id,
            ]);

        $assignment = DispatchAssignment::firstOrFail();
        $this->assertSame($amb->organization_id, $assignment->organization_id);
    }

    public function test_citizen_forbidden_from_dispatch(): void
    {
        $cit = User::factory()->create(['account_type' => 'citizen']);
        $this->actingAs($cit)->get(route('admin.dispatch.index'))->assertForbidden();
    }
}
