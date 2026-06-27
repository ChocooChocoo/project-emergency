<?php

namespace Tests\Feature;

use App\Models\Organization;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrganizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, PlanSeeder::class, UserSeeder::class]);
    }

    private function superAdmin(): User
    {
        return User::where('email', 'superadmin@rescue.test')->firstOrFail();
    }

    public function test_super_admin_can_create_org_as_pending(): void
    {
        $plan = Plan::where('code', 'partner_basic')->firstOrFail();

        $this->actingAs($this->superAdmin())
            ->post(route('admin.organizations.store'), [
                'name' => 'Cavite Rescue Inc.',
                'org_type' => 'partner',
                'plan_id' => $plan->id,
                'service_city' => 'Dasmariñas',
            ])->assertRedirect();

        $org = Organization::where('name', 'Cavite Rescue Inc.')->firstOrFail();
        $this->assertSame('pending_review', $org->organization_status);
        $this->assertFalse($org->is_active);
        $this->assertSame($plan->id, $org->subscription->plan_id);
    }

    public function test_lgu_approval_activates_org(): void
    {
        $plan = Plan::where('code', 'partner_basic')->firstOrFail();
        $org = Organization::create([
            'name' => 'Pending Org', 'org_type' => 'partner',
            'organization_status' => 'pending_review', 'is_active' => false,
        ]);
        $org->subscription()->create(['plan_id' => $plan->id, 'status' => 'trialing']);

        $this->actingAs($this->superAdmin())
            ->patch(route('admin.org-approvals.approve', $org))
            ->assertRedirect();

        $org->refresh();
        $this->assertSame('active', $org->organization_status);
        $this->assertTrue($org->is_active);
        $this->assertTrue($org->is_approved);
    }

    public function test_citizen_is_forbidden_from_org_admin(): void
    {
        $cit = User::factory()->create(['account_type' => 'citizen']);
        $this->actingAs($cit)->get(route('admin.organizations.index'))->assertForbidden();
    }
}
