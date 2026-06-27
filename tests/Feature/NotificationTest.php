<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Notifier;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
    }

    public function test_notifier_creates_a_row(): void
    {
        $user = User::factory()->create();

        $n = Notifier::send($user->id, 'Hello', 'A message', 'system');

        $this->assertDatabaseHas('notifications', [
            'id' => $n->id, 'user_id' => $user->id, 'title' => 'Hello', 'is_read' => false,
        ]);
    }

    public function test_user_sees_only_own_notifications(): void
    {
        $alice = User::factory()->create();
        $bob = User::factory()->create();
        Notifier::send($alice->id, 'For Alice', 'msg');
        Notifier::send($bob->id, 'For Bob', 'msg');

        $this->actingAs($alice)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('For Alice')
            ->assertDontSee('For Bob');
    }

    public function test_mark_read_flips_state(): void
    {
        $user = User::factory()->create();
        $n = Notifier::send($user->id, 'Read me', 'msg');

        $this->actingAs($user)
            ->patch(route('admin.notifications.read', $n))
            ->assertRedirect();

        $fresh = $n->fresh();
        $this->assertTrue($fresh->is_read);
        $this->assertNotNull($fresh->read_at);
    }

    public function test_cannot_mark_another_users_notification(): void
    {
        $owner = User::factory()->create();
        $intruder = User::factory()->create();
        $n = Notifier::send($owner->id, 'Private', 'msg');

        $this->actingAs($intruder)
            ->patch(route('admin.notifications.read', $n))
            ->assertForbidden();

        $this->assertFalse($n->fresh()->is_read);
    }
}
