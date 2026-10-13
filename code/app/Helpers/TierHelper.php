<?php

namespace App\Helpers;

/**
 * Decay points and sociocultural tiers from Listing 3 and Tables 2–3.
 */
class TierHelper
{
    public const BASE_POINTS = 100;

    public const DECAY_FACTOR = [
        1 => 1.0,
        2 => 0.9,
        3 => 0.75,
    ];

    public const DECAY_POINTS = [
        1 => 100,
        2 => 90,
        3 => 75,
    ];

    /**
     * Success rate across the 28 challenges. Approved attempts score 100, 90, or 75.
     * Any other attempt count on an approved row scores 0. Unapproved rows score 0.
     */
    public static function calculateSuccessRate(array $submissions, int $total = 28): float
    {
        $totalPoints = 0;
        foreach ($submissions as $sub) {
            if ($sub->status === 'approved') {
                $totalPoints += self::DECAY_POINTS[$sub->attempts_used] ?? 0;
            }
        }

        return round(($totalPoints / ($total * self::BASE_POINTS)) * 100, 1);
    }

    /**
     * @return array{0:string,1:string} tier name, then Gen Z title
     */
    public static function getTier(float $rate): array
    {
        return match (true) {
            $rate >= 100 => ['Legendary', 'Si Paling Sepuh'],
            $rate >= 91 => ['Elite', 'Suhu Database'],
            $rate >= 81 => ['Expert', 'Raja Query'],
            $rate >= 61 => ['Advanced', 'Kang Inner Join'],
            $rate >= 41 => ['Intermediate', 'Pawang Error'],
            $rate >= 21 => ['Apprentice', 'Kuli Database'],
            default => ['Beginner', 'Sobat Syntax Error'],
        };
    }
}
