<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use App\Support\PortalRouter;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CitizenPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, PlanSeeder::class, UserSeeder::class]);
    }

    private function incidentFor(?int $userId): Incident
    {
        return Incident::create([
            'request_code' => 'REQ-'.strtoupper((string) Str::ulid()),
            'request_type' => 'one_tap',
            'user_id' => $userId,
            'status' => 'pending',
            'pickup_lat' => '14.5995',
            'pickup_lng' => '120.9842',
            'pickup_address' => 'Rizal Park, Manila',
            'is_public_tracking' => true,
        ]);
    }

    public function test_citizen_lands_on_portal_home_not_403(): void
    {
        $citizen = User::factory()->create();

        $this->actingAs($citizen)->get(route('citizen.home'))->assertOk();
    }

    public function test_portal_router_sends_citizen_home_and_admin_dashboard(): void
    {
        $citizen = User::factory()->create();
        $superAdmin = User::where('email', 'superadmin@rescue.test')->firstOrFail();

        $this->assertSame('citizen.home', PortalRouter::homeRouteFor($citizen));
        $this->assertSame('dashboard', PortalRouter::homeRouteFor($superAdmin));
    }

    public function test_history_shows_only_own_incidents(): void
    {
        $citizen = User::factory()->create();
        $other = User::factory()->create();

        $mine = $this->incidentFor($citizen->id);
        $theirs = $this->incidentFor($other->id);

        $this->actingAs($citizen)->get(route('citizen.history'))
            ->assertOk()
            ->assertSee($mine->request_code)
            ->assertDontSee($theirs->request_code);
    }

    public function test_citizen_still_forbidden_on_console(): void
    {
        $citizen = User::factory()->create();

        $this->actingAs($citizen)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($citizen)->get(route('admin.incidents.index'))->assertForbidden();
    }

    public function test_profile_update_persists_for_self(): void
    {
        $citizen = User::factory()->create();

        $this->actingAs($citizen)->put(route('citizen.profile.update'), [
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'phone' => '09171234567',
        ])->assertRedirect(route('citizen.profile'));

        $this->assertDatabaseHas('users', [
            'id' => $citizen->id,
            'first_name' => 'Juan',
            'phone' => '09171234567',
        ]);
    }

    public function test_medical_update_persists_json(): void
    {
        $citizen = User::factory()->create();

        $this->actingAs($citizen)->put(route('citizen.medical.update'), [
            'blood_type' => 'O+',
            'allergies' => 'Penicillin',
        ])->assertRedirect(route('citizen.medical'));

        $this->assertSame('O+', $citizen->fresh()->medical_info['blood_type']);
        $this->assertSame('Penicillin', $citizen->fresh()->medical_info['allergies']);
    }
}
