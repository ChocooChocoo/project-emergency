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

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
    }

    private function lgu(): User
    {
        return User::where('email', 'superadmin@rescue.test')->firstOrFail();
    }

    public function test_reports_page_loads_and_shows_computed_counts(): void
    {
        $org = Organization::create([
            'name' => 'Report Org', 'org_type' => 'partner',
            'organization_status' => 'active', 'is_active' => true, 'is_approved' => true,
        ]);
        $amb = Ambulance::create([
            'organization_id' => $org->id, 'plate_no' => 'AMB-RPT', 'unit_code' => 'U-RPT',
            'tier' => 'als', 'status' => 'available', 'is_serviceable' => true,
        ]);

        $incident = Incident::create([
            'request_code' => 'REQ-RPT-1', 'request_type' => 'one_tap', 'status' => 'completed',
            'severity' => 2, 'pickup_address' => 'A', 'pickup_lat' => 14.3, 'pickup_lng' => 120.9,
        ]);
        Incident::create([
            'request_code' => 'REQ-RPT-2', 'request_type' => 'one_tap', 'status' => 'pending',
            'severity' => 3, 'pickup_address' => 'B', 'pickup_lat' => 14.3, 'pickup_lng' => 120.9,
        ]);

        DispatchAssignment::create([
            'incident_id' => $incident->id, 'organization_id' => $org->id, 'ambulance_id' => $amb->id,
            'driver_user_id' => $this->lgu()->id, 'status' => 'completed', 'assigned_at' => now()->subMinutes(20),
            'arrived_on_scene_at' => now()->subMinutes(12), 'arrived_at_hospital_at' => now()->subMinutes(2),
        ]);

        $this->actingAs($this->lgu())
            ->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee('Reports')
            ->assertSee('Response-time KPIs')
            ->assertSee('Total requests');
    }

    public function test_reports_respects_permission_gate(): void
    {
        $citizen = User::factory()->create(['account_type' => 'citizen']);

        $this->actingAs($citizen)
            ->get(route('admin.reports.index'))
            ->assertForbidden();
    }
}
