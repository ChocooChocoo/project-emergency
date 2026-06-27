<?php

namespace Tests\Feature;

use App\Models\Incident;
use App\Models\User;
use App\Support\PortalRouter;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LguPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
    }

    private function lgu(): User
    {
        return User::where('email', 'lgu@rescue.test')->firstOrFail();
    }

    private function superAdmin(): User
    {
        return User::where('email', 'superadmin@rescue.test')->firstOrFail();
    }

    /** Console users land on dashboard; citizens land on the public intake. */
    public function test_portal_router_routes_by_role(): void
    {
        $citizen = User::where('email', 'citizen@rescue.test')->firstOrFail();

        $this->assertSame('dashboard', PortalRouter::homeRouteFor($this->lgu()));
        $this->assertSame('dashboard', PortalRouter::homeRouteFor($this->superAdmin()));
        $this->assertSame('request.create', PortalRouter::homeRouteFor($citizen));
    }

    public function test_lgu_can_view_dashboard(): void
    {
        $this->actingAs($this->lgu())->get(route('dashboard'))->assertOk();
    }

    public function test_lgu_can_edit_and_save_city_settings(): void
    {
        $this->actingAs($this->lgu())->get(route('admin.config.edit'))->assertOk();

        $this->actingAs($this->lgu())
            ->put(route('admin.config.update'), ['dss_timeout_seconds' => 90])
            ->assertRedirect();

        $stored = DB::table('system_configurations')
            ->where('scope', 'global')->where('config_key', 'dss_timeout_seconds')
            ->value('config_value');
        $this->assertSame('90', (string) $stored);
    }

    public function test_city_settings_rejects_out_of_range_timeout(): void
    {
        $this->actingAs($this->lgu())
            ->put(route('admin.config.update'), ['dss_timeout_seconds' => 1])
            ->assertSessionHasErrors('dss_timeout_seconds');
    }

    /** Role cleanup: the LGU no longer holds operational permissions. */
    public function test_lgu_is_forbidden_from_operational_routes(): void
    {
        foreach (['admin.dispatch.index', 'admin.hospitals.index'] as $route) {
            $this->actingAs($this->lgu())->get(route($route))->assertForbidden();
        }

        // A care route (record-care). Bind a real incident so the 404 doesn't mask the 403.
        $incident = Incident::create([
            'request_code' => 'REQ-'.strtoupper(uniqid()),
            'request_type' => 'one_tap', 'status' => 'pending', 'severity' => 1,
            'pickup_lat' => 14.331, 'pickup_lng' => 120.931, 'pickup_address' => 'Test St',
        ]);
        $this->actingAs($this->lgu())->get(route('admin.care.show', $incident))->assertForbidden();
    }

    /** SA is oversight-only now: reaches oversight screens, 403 on operational ones. */
    public function test_super_admin_oversight_only(): void
    {
        foreach (['admin.users.index', 'admin.archive.index', 'admin.audit.index', 'admin.config.edit'] as $route) {
            $this->actingAs($this->superAdmin())->get(route($route))->assertOk();
        }
        foreach (['admin.dispatch.index', 'admin.hospitals.index'] as $route) {
            $this->actingAs($this->superAdmin())->get(route($route))->assertForbidden();
        }
    }
}
