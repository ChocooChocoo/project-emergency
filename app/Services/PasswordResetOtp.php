<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/** Password-reset codes against tbl_password_reset_codes. Stored hashed. */
class PasswordResetOtp
{
    public const TTL_MINUTES = 15;

    public static function issue(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('password_reset_codes')->where('user_id', $user->id)->whereNull('used_at')->delete();

        DB::table('password_reset_codes')->insert([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'created_at' => now(),
        ]);

        $user->notify(new PasswordResetCodeNotification($code));

        return $code;
    }

    /** Consume a valid code. Returns true and marks it used; false if invalid/expired. */
    public static function consume(User $user, string $code): bool
    {
        $row = DB::table('password_reset_codes')
            ->where('user_id', $user->id)
            ->whereNull('used_at')
            ->latest('id')
            ->first();

        if (! $row || now()->greaterThan($row->expires_at) || ! Hash::check($code, $row->code_hash)) {
            return false;
        }

        DB::table('password_reset_codes')->where('id', $row->id)->update(['used_at' => now()]);

        return true;
    }
}
