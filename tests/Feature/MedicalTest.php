<?php

namespace Tests\Feature;

use App\Models\Ambulance;
use App\Models\DispatchAssignment;
use App\Models\Hospital;
use App\Models\HospitalEndorsement;
use App\Models\Incident;
use App\Models\Organization;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MedicalTest extends TestCase
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

    private function incidentWithUnit(): Incident
    {
        $org = Organization::create([
            'name' => 'Med Org '.uniqid(), 'org_type' => 'partner',
            'organization_status' => 'active', 'is_active' => true, 'is_approved' => true,
        ]);
        $amb = Ambulance::create(['organization_id' => $org->id, 'plate_no' => 'MED-'.uniqid(), 'status' => 'on_scene']);
        $incident = Incident::create([
            'request_code' => 'REQ-'.strtoupper(uniqid()), 'request_type' => 'detailed',
            'status' => 'on_scene', 'severity' => 2, 'pickup_address' => 'Y', 'pickup_lat' => 14.3, 'pickup_lng' => 120.9,
        ]);
        DispatchAssignment::create([
            'incident_id' => $incident->id, 'organization_id' => $org->id, 'ambulance_id' => $amb->id,
            'driver_user_id' => $this->superAdmin()->id, 'status' => 'arrived_on_scene', 'assigned_at' => now(),
        ]);

        return $incident;
    }

    public function test_record_vitals_and_treatment(): void
    {
        $incident = $this->incidentWithUnit();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.care.vitals.store', $incident), ['bp_systolic' => 120, 'bp_diastolic' => 80, 'pulse_rate' => 72])
            ->assertRedirect();
        $this->assertDatabaseHas('vitals_entries', ['incident_id' => $incident->id, 'bp_systolic' => 120]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.care.treatments.store', $incident), ['treatment_type' => 'Oxygen therapy'])
            ->assertRedirect();
        $this->assertDatabaseHas('treatment_records', ['incident_id' => $incident->id, 'treatment_type' => 'Oxygen therapy']);
    }

    public function test_vitals_out_of_range_rejected(): void
    {
        $incident = $this->incidentWithUnit();
        $this->actingAs($this->superAdmin())
            ->post(route('admin.care.vitals.store', $incident), ['oxygen_saturation' => 250])
            ->assertSessionHasErrors('oxygen_saturation');
    }

    public function test_endorse_accept_handoff_completes_incident(): void
    {
        $incident = $this->incidentWithUnit();
        $hospital = Hospital::create(['name' => 'Test Hospital', 'is_er_open' => true, 'is_active' => true]);

        $this->actingAs($this->superAdmin())
            ->post(route('admin.hospitals.endorse', $incident), ['hospital_id' => $hospital->id])
            ->assertRedirect();
        $endorsement = HospitalEndorsement::firstOrFail();

        $this->actingAs($this->superAdmin())
            ->patch(route('admin.hospitals.respond', $endorsement), ['decision' => 'accepted']);
        $this->assertSame('accepted', $endorsement->fresh()->status);

        $this->actingAs($this->superAdmin())
            ->patch(route('admin.hospitals.handoff', $endorsement));

        $this->assertSame('completed', $incident->fresh()->status);
        $this->assertSame('completed', $endorsement->fresh()->handoff_status);
        $this->assertDatabaseHas('handoff_summaries', ['incident_id' => $incident->id]);
    }
}
