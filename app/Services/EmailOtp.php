<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\EmailOtpNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Email OTP issue/verify against tbl_email_verification_codes.
 * Codes are stored hashed; we never persist the plaintext.
 *
 * ponytail: 6-digit numeric code, 10-min expiry, 5-attempt cap — fixed for capstone;
 * tune via the constants if the panel wants stricter rules.
 */
class EmailOtp
{
    public const TTL_MINUTES = 10;

    public const MAX_ATTEMPTS = 5;

    /** Generate a fresh code, store its hash, email it. Returns the plaintext (for dev banner). */
    public static function issue(User $user): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::table('email_verification_codes')->where('user_id', $user->id)->whereNull('verified_at')->delete();

        DB::table('email_verification_codes')->insert([
            'user_id' => $user->id,
            'code_hash' => Hash::make($code),
            'attempt_count' => 0,
            'expires_at' => now()->addMinutes(self::TTL_MINUTES),
            'sent_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $user->notify(new EmailOtpNotification($code));

        return $code;
    }

    /** Verify a submitted code. Returns true on success and marks the user verified. */
    public static function verify(User $user, string $code): bool
    {
        $row = DB::table('email_verification_codes')
            ->where('user_id', $user->id)
            ->whereNull('verified_at')
            ->latest('id')
            ->first();

        if (! $row || now()->greaterThan($row->expires_at) || $row->attempt_count >= self::MAX_ATTEMPTS) {
            return false;
        }

        if (! Hash::check($code, $row->code_hash)) {
            DB::table('email_verification_codes')->where('id', $row->id)
                ->update(['attempt_count' => $row->attempt_count + 1, 'last_attempt_at' => now()]);

            return false;
        }

        DB::table('email_verification_codes')->where('id', $row->id)->update(['verified_at' => now()]);

        return true;
    }
}
