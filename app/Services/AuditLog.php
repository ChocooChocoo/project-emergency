<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;

/** Append-only writer for tbl_audit_logs (SECURITY IMPROVEMENTS §2.6). */
class AuditLog
{
    public static function record(string $action, ?string $modelType = null, ?int $modelId = null, ?array $newValues = null): void
    {
        $user = Auth::user();

        DB::table('audit_logs')->insert([
            'user_id' => $user?->id,
            'role' => $user?->account_type,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'created_at' => now(),
        ]);
    }
}
