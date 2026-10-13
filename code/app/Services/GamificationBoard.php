<?php

namespace App\Services;

use App\Helpers\TierHelper;

/**
 * Holds the success rate and leaderboard position updated by Algorithm 1.
 */
class GamificationBoard
{
    /** @var array<string,float> */
    private array $rates = [];

    /** @var array<string,int> */
    private array $ranks = [];

    /** @return array{0:string,1:string} */
    public function updateTier(string $user, float $cumulativeScore): array
    {
        $this->rates[$user] = $cumulativeScore;

        return TierHelper::getTier($cumulativeScore);
    }

    public function updateLeaderboard(string $user): void
    {
        if (!array_key_exists($user, $this->rates)) {
            return;
        }
        $rates = $this->rates;
        arsort($rates);
        $rank = 1;
        $ranks = [];
        foreach (array_keys($rates) as $id) {
            $ranks[$id] = $rank;
            $rank++;
        }
        $this->ranks = $ranks;
    }

    public function rate(string $user): ?float
    {
        return $this->rates[$user] ?? null;
    }

    public function rank(string $user): ?int
    {
        return $this->ranks[$user] ?? null;
    }

    /** @return array{0:string,1:string}|null */
    public function tier(string $user): ?array
    {
        $rate = $this->rate($user);
        if ($rate === null) {
            return null;
        }

        return TierHelper::getTier($rate);
    }
}
