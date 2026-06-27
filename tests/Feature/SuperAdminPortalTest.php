<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminPortalTest extends TestCase
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

    private function lgu(): User
    {
        return User::where('email', 'lgu@rescue.test')->firstOrFail();
    }

    public function test_super_admin_reaches_oversight_screens(): void
    {
        foreach (['admin.archive.index', 'admin.audit.index'] as $route) {
            $this->actingAs($this->superAdmin())->get(route($route))->assertOk();
        }
    }

    public function test_super_admin_is_forbidden_from_operational_routes(): void
    {
        $this->actingAs($this->superAdmin())->get(route('admin.dispatch.index'))->assertForbidden();
        $this->actingAs($this->superAdmin())->get(route('admin.hospitals.index'))->assertForbidden();
        $this->actingAs($this->superAdmin())->post(route('admin.ambulances.store'))->assertForbidden();

        // Care route needs a bound incident so a 404 doesn't mask the 403.
        $incident = Incident::create([
            'request_code' => 'REQ-'.strtoupper(uniqid()),
            'request_type' => 'one_tap', 'status' => 'pending', 'severity' => 1,
            'pickup_lat' => 14.331, 'pickup_lng' => 120.931, 'pickup_address' => 'Test St',
        ]);
        $this->actingAs($this->superAdmin())->get(route('admin.care.show', $incident))->assertForbidden();
    }

    public function test_archive_registry_lists_and_restores(): void
    {
        $victim = User::factory()->create([
            'is_archived' => true, 'is_active' => false, 'archived_at' => now(),
            'archived_by' => $this->superAdmin()->id, 'archive_reason' => 'test',
        ]);
        DB::table('archival_logs')->insert([
            'table_name' => 'users', 'record_id' => $victim->id,
            'archived_by' => $this->superAdmin()->id, 'archive_reason' => 'test', 'archived_at' => now(),
        ]);

        $this->actingAs($this->superAdmin())->get(route('admin.archive.index'))
            ->assertOk()->assertSee('#'.$victim->id);

        $this->actingAs($this->superAdmin())
            ->patch(route('admin.users.restore', $victim))->assertRedirect();

        $this->assertFalse($victim->fresh()->is_archived);
    }

    /** Cards are permission-scoped, not role-scoped: LGU lacks manage-archive. */
    public function test_lgu_cannot_reach_archive_registry(): void
    {
        $this->actingAs($this->lgu())->get(route('admin.archive.index'))->assertForbidden();
    }
}
