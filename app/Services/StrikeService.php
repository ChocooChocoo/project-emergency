<?php

namespace App\Services;

use App\Models\DeviceToken;

/**
 * R4 — device-UUID anti-abuse. Three false alarms inside 30 days blocks the device.
 *
 * ponytail: rolling window approximated by a single counter that resets when the last
 * strike is older than the window. Per-strike timestamp history is the upgrade path if
 * an exact sliding window is ever needed.
 */
class StrikeService
{
    public const STRIKE_LIMIT = 3;

    public const WINDOW_DAYS = 30;

    public static function isBlocked(?string $deviceUuid): bool
    {
        if (! $deviceUuid) {
            return false;
        }

        return DeviceToken::where('device_uuid', $deviceUuid)->where('is_blocked', true)->exists();
    }

    /** Record a false alarm; returns the device token after applying the strike. */
    public static function recordFalseAlarm(string $deviceUuid, ?int $userId = null): DeviceToken
    {
        $device = DeviceToken::firstOrNew(['device_uuid' => $deviceUuid]);
        $device->user_id ??= $userId;

        // Reset the counter if the previous strike fell outside the window.
        if ($device->last_flagged_at && $device->last_flagged_at->lt(now()->subDays(self::WINDOW_DAYS))) {
            $device->false_alarm_count = 0;
        }

        $device->false_alarm_count++;
        $device->last_flagged_at = now();
        if ($device->false_alarm_count >= self::STRIKE_LIMIT && ! $device->is_blocked) {
            $device->is_blocked = true;
            $device->blocked_at = now();
        }
        $device->save();

        AuditLog::record('device.false_alarm', DeviceToken::class, $device->id);

        return $device;
    }

    public static function setBlocked(DeviceToken $device, bool $blocked): void
    {
        $device->update(['is_blocked' => $blocked, 'blocked_at' => $blocked ? now() : null]);
        AuditLog::record($blocked ? 'device.blocked' : 'device.unblocked', DeviceToken::class, $device->id);
    }
}
