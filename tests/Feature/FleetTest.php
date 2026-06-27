<?php

namespace Tests\Feature;

use App\Models\Ambulance;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FleetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, PlanSeeder::class, UserSeeder::class]);
    }

    private function superAdmin(): User
    {
        // Org-admin-equivalent: super_admin is oversight-only now; field roles own this perm.
        return $this->actorWith(['manage-fleet']);
    }

    private function org(string $planCode = 'partner_basic'): Organization
    {
        $plan = Plan::where('code', $planCode)->firstOrFail();
        $org = Organization::create([
            'name' => 'Fleet Org '.uniqid(), 'org_type' => 'partner',
            'organization_status' => 'active', 'is_active' => true,
        ]);
        $org->subscription()->create(['plan_id' => $plan->id, 'status' => 'active']);

        return $org;
    }

    public function test_can_register_ambulance_with_tier_and_equipment(): void
    {
        $org = $this->org();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.ambulances.store'), [
                'organization_id' => $org->id,
                'plate_no' => 'ABC-1234',
                'tier' => 'als',
                'has_ventilator' => '1',
                'has_oxygen' => '1',
                'capacity_patients' => 2,
                'status' => 'available',
                'is_serviceable' => '1',
            ])->assertRedirect();

        $amb = Ambulance::where('plate_no', 'ABC-1234')->firstOrFail();
        $this->assertSame('als', $amb->tier);
        $this->assertTrue($amb->has_ventilator);
        $this->assertTrue($amb->has_oxygen);
        $this->assertFalse($amb->has_aed);
    }

    public function test_plate_unique_per_org(): void
    {
        $org = $this->org();
        Ambulance::create(['organization_id' => $org->id, 'plate_no' => 'DUP-1']);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.ambulances.store'), [
                'organization_id' => $org->id, 'plate_no' => 'DUP-1', 'status' => 'available',
            ])->assertSessionHasErrors('plate_no');
    }

    public function test_plan_cap_blocks_over_limit_registration(): void
    {
        // partner_basic caps ambulances at 3.
        $org = $this->org('partner_basic');
        foreach (range(1, 3) as $i) {
            Ambulance::create(['organization_id' => $org->id, 'plate_no' => "CAP-{$i}"]);
        }

        $this->actingAs($this->superAdmin())
            ->post(route('admin.ambulances.store'), [
                'organization_id' => $org->id, 'plate_no' => 'CAP-OVER', 'status' => 'available',
            ])->assertSessionHas('error');

        $this->assertDatabaseMissing('ambulances', ['plate_no' => 'CAP-OVER']);
    }

    public function test_citizen_forbidden_from_fleet(): void
    {
        $cit = User::factory()->create(['account_type' => 'citizen']);
        $this->actingAs($cit)->get(route('admin.ambulances.index'))->assertForbidden();
    }
}
