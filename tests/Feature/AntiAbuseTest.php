<?php

namespace Tests\Feature;

use App\Models\DeviceToken;
use App\Models\Incident;
use App\Services\StrikeService;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AntiAbuseTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, UserSeeder::class]);
    }

    private function intakePayload(): array
    {
        return [
            'request_type' => 'one_tap',
            'pickup_lat' => 14.33, 'pickup_lng' => 120.93, 'pickup_address' => 'Somewhere',
        ];
    }

    public function test_third_false_alarm_blocks_device(): void
    {
        $uuid = 'device-abc';
        StrikeService::recordFalseAlarm($uuid);
        StrikeService::recordFalseAlarm($uuid);
        $this->assertFalse(StrikeService::isBlocked($uuid));

        StrikeService::recordFalseAlarm($uuid);
        $this->assertTrue(StrikeService::isBlocked($uuid));
        $this->assertDatabaseHas('device_tokens', ['device_uuid' => $uuid, 'is_blocked' => true]);
    }

    public function test_blocked_device_intake_rejected(): void
    {
        DeviceToken::create(['device_uuid' => 'blocked-dev', 'false_alarm_count' => 3, 'is_blocked' => true]);

        $this->withCookie('device_uuid', 'blocked-dev')
            ->post(route('request.store'), $this->intakePayload())
            ->assertSessionHas('error');

        $this->assertDatabaseCount('incidents', 0);
    }

    public function test_cancellation_holds_for_field_verification(): void
    {
        $incident = Incident::create([
            'request_code' => 'REQ-CANCEL', 'request_type' => 'one_tap', 'status' => 'dispatched',
            'severity' => 2, 'pickup_address' => 'Z', 'pickup_lat' => 14.3, 'pickup_lng' => 120.9,
        ]);

        $this->patch(route('request.cancel', $incident->request_code))->assertRedirect();

        // Never hard-cancelled — held pending for a responder to verify.
        $this->assertSame('pending', $incident->fresh()->status);
        $this->assertDatabaseHas('incident_updates', [
            'incident_id' => $incident->id, 'care_status' => 'needs_field_verification',
        ]);
    }
}
