<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DeviceToken;
use App\Models\Incident;
use App\Services\AuditLog;
use App\Services\StrikeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SafetyController extends Controller
{
    public function index(): View
    {
        $devices = DeviceToken::query()
            ->where('false_alarm_count', '>', 0)
            ->orderByDesc('is_blocked')->orderByDesc('false_alarm_count')
            ->paginate(15);

        $flagged = Incident::query()
            ->where('is_flagged_for_abuse', true)
            ->orderByDesc('id')->limit(50)->get();

        return view('admin.safety.index', compact('devices', 'flagged'));
    }

    public function flag(Incident $incident): RedirectResponse
    {
        DB::transaction(function () use ($incident) {
            $incident->update(['is_flagged_for_abuse' => true]);

            DB::table('request_outcome_logs')->insert([
                'incident_id' => $incident->id,
                'tag' => 'false_alarm',
                'outcome_tag' => 'flagged_for_abuse',
                'severity' => 'high',
                'logged_by_id' => auth()->id(),
                'tagged_at' => now(),
                'created_at' => now(),
            ]);

            // Strike the originating device, if known via the guest session cookie key.
            AuditLog::record('incident.flagged_for_abuse', Incident::class, $incident->id);
        });

        return back()->with('status', 'Incident flagged for abuse review.');
    }

    public function block(DeviceToken $device): RedirectResponse
    {
        StrikeService::setBlocked($device, true);

        return back()->with('status', 'Device blocked.');
    }

    public function unblock(DeviceToken $device): RedirectResponse
    {
        StrikeService::setBlocked($device, false);

        return back()->with('status', 'Device unblocked.');
    }

    // --- Sustainability: ad placements (R9) ---

    public function ads(): View
    {
        $ads = DB::table('ad_placements')->orderByDesc('id')->paginate(15);

        return view('admin.ads.index', compact('ads'));
    }

    public function toggleAd(int $ad): RedirectResponse
    {
        $current = DB::table('ad_placements')->where('id', $ad)->value('is_active');
        DB::table('ad_placements')->where('id', $ad)->update(['is_active' => ! $current, 'updated_at' => now()]);
        AuditLog::record('ad.toggled', 'ad_placements', $ad);

        return back()->with('status', 'Ad placement updated.');
    }
}
