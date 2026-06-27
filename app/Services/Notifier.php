<?php

namespace App\Services;

use App\Models\Notification;

/**
 * In-app notification writer (S11). Mirrors the AuditLog::record style — one call site,
 * no ceremony. ponytail: DB-table notifications, swap to Laravel's notification system if
 * channels (mail/SMS/push) are ever needed.
 */
class Notifier
{
    public static function send(int $userId, string $title, string $message, string $type = 'system', ?array $data = null): Notification
    {
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'data_json' => $data,
            'is_read' => false,
        ]);
    }
}
