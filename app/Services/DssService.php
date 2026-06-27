<?php

namespace App\Services;

use App\Models\Ambulance;
use App\Models\Incident;
use Illuminate\Support\Collection;

/**
 * Headless Decision Support System — ranks idle ambulances for an incident.
 *
 * ponytail: synchronous scoring only. The roadmap's queued AutomaticThrowJob +
 * 30–90s countdown + ReassignJob is the upgrade path; a dispatcher picks from this
 * ranked list manually for now.
 */
class DssService
{
    /** Average urban speed used to turn distance into a rough ETA. */
    private const AVG_SPEED_KMH = 30;

    /**
     * Rank available ambulances for this incident, best first.
     * Each row: ['ambulance' => Ambulance, 'distance_km' => float, 'eta_minutes' => int, 'score' => float, 'dss_rank' => int].
     */
    public static function rank(Incident $incident): Collection
    {
        $units = Ambulance::query()
            ->with('organization', 'currentDriver')
            ->where('status', 'available')
            ->where('is_serviceable', true)
            ->where('is_archived', false)
            ->whereHas('organization', fn ($q) => $q
                ->where('is_active', true)->where('is_approved', true)->where('is_archived', false))
            ->get();

        $lat = $incident->pickup_lat !== null ? (float) $incident->pickup_lat : null;
        $lng = $incident->pickup_lng !== null ? (float) $incident->pickup_lng : null;

        return $units
            ->map(function (Ambulance $a) use ($incident, $lat, $lng) {
                $dist = ($lat !== null && $a->last_lat !== null)
                    ? self::haversineKm($lat, $lng, (float) $a->last_lat, (float) $a->last_lng)
                    : null;
                $eta = $dist !== null ? (int) ceil($dist / self::AVG_SPEED_KMH * 60) : null;

                return [
                    'ambulance' => $a,
                    'distance_km' => $dist,
                    'eta_minutes' => $eta,
                    'score' => self::score($incident, $a, $dist),
                ];
            })
            ->sortByDesc('score')
            ->values()
            ->map(function ($row, $i) {
                $row['dss_rank'] = $i + 1;

                return $row;
            });
    }

    /**
     * Higher is better. Distance dominates; ALS tier and richer equipment add a bonus
     * weighted by case severity (severity 1 = most urgent).
     */
    private static function score(Incident $incident, Ambulance $a, ?float $distanceKm): float
    {
        $proximity = $distanceKm === null ? 50.0 : max(0, 100 - $distanceKm * 10); // ~10 km wipes the proximity points

        $urgency = 5 - min(4, max(1, (int) ($incident->severity ?? 4))); // severity 1 → 4, severity 4 → 1
        $tierBonus = $a->tier === 'als' ? 10 : 0;
        $equipBonus = collect(array_keys(Ambulance::EQUIPMENT))->sum(fn ($flag) => $a->{$flag} ? 1 : 0);

        return round($proximity + $urgency * ($tierBonus + $equipBonus), 2);
    }

    private static function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $h = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earth * 2 * atan2(sqrt($h), sqrt(1 - $h));
    }
}
