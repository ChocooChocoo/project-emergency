<?php

namespace App\Services;

use App\Models\GuestSession;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Anonymous identity for no-login requests. Carries the guest key in a long-lived cookie;
 * the model enforces the per-guest request quota. Reused by the web intake form (and the
 * future mobile API). ponytail: cookie-backed key, no separate device table here (S10 owns
 * device_tokens / strikes).
 */
class GuestSessionService
{
    public const COOKIE = 'guest_key';

    /** Resolve the current guest session from the request cookie, creating one if absent. */
    public static function resolveOrCreate(Request $request): GuestSession
    {
        $key = $request->cookie(self::COOKIE);

        $session = $key ? GuestSession::where('guest_key', $key)->first() : null;

        if (! $session) {
            $session = GuestSession::create([
                'guest_key' => (string) Str::uuid(),
                'ip_first_seen' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 250, ''),
                'requests_limit' => 2,
                'requests_used' => 0,
                'is_active' => true,
                'last_seen_at' => now(),
            ]);
        }

        return $session;
    }

    /** Record that this guest consumed one request slot. */
    public static function consume(GuestSession $session): void
    {
        $session->increment('requests_used');
        $session->update(['last_seen_at' => now()]);
    }
}
