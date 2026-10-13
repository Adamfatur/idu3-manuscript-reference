<?php

namespace App\Services;

use App\Helpers\TierHelper;

/**
 * Algorithm 1. Direct approval is identical output and the same known tables.
 * Every other executed query goes to the LLM. Decay applies only after approval.
 * The fast path does not produce feedback text. Algorithm 1 leaves that variable unset.
 */
class RewardAllocator
{
    /** @param list<string> $knownTables */
    public function __construct(
        private readonly GamificationBoard $board,
        private readonly array $knownTables,
    ) {
    }

    /**
     * @param array{user:string,query:string,attempts_used:int} $submission
     * @param array{expected_sql:string} $challenge
     * @param list<object> $approvedSubmissions earlier approved rows for this user
     * @return array{score:float,decision:string,feedback:?string}
     */
    public function allocate(
        array $submission,
        array $challenge,
        QueryExecutor $executor,
        LlmEvaluator $llm,
        array $approvedSubmissions,
        int $totalChallenges = 28,
    ): array {
        $qs = $submission['query'];
        $qe = $challenge['expected_sql'];

        try {
            $os = $executor->execute($qs);
        } catch (SqlExecutionException $error) {
            return [
                'score' => 0,
                'decision' => 'rejected',
                'feedback' => $error->getMessage(),
            ];
        }

        $expectedOutput = $executor->execute($qe);
        $feedback = null;
        if ($os == $expectedOutput && $this->sameTables($qs, $qe)) {
            $decision = 'approved';
            $score = 100;
        } else {
            $review = $llm->evaluate($qs, $qe, $os);
            $decision = $review['decision'];
            $feedback = $review['feedback'];
            if ($decision === 'rejected') {
                return [
                    'score' => 0,
                    'decision' => 'rejected',
                    'feedback' => $feedback,
                ];
            }
            if ($decision !== 'approved') {
                throw new \RuntimeException('LLM decision must be approved or rejected');
            }
            $score = $review['score'];
        }

        $attempt = (int) $submission['attempts_used'];
        if (!isset(TierHelper::DECAY_FACTOR[$attempt])) {
            throw new \InvalidArgumentException('Decay D(n) is defined for attempts 1, 2, and 3');
        }
        $score *= TierHelper::DECAY_FACTOR[$attempt];

        $approvedSubmissions[] = (object) [
            'status' => 'approved',
            'attempts_used' => $attempt,
        ];
        $rate = TierHelper::calculateSuccessRate($approvedSubmissions, $totalChallenges);
        $this->board->updateTier($submission['user'], $rate);
        $this->board->updateLeaderboard($submission['user']);

        return [
            'score' => $score,
            'decision' => 'approved',
            'feedback' => $feedback,
        ];
    }

    private function sameTables(string $studentQuery, string $expectedQuery): bool
    {
        return $this->referencedTables($studentQuery) === $this->referencedTables($expectedQuery);
    }

    /** @return list<string> */
    private function referencedTables(string $sql): array
    {
        $stripped = (new SandboxSQLService($this->knownTables, ''))->strippedSql($sql);
        $found = [];
        foreach ($this->knownTables as $table) {
            if (preg_match('/\b' . preg_quote($table, '/') . '\b/i', $stripped) === 1) {
                $found[] = strtolower($table);
            }
        }
        $found = array_values(array_unique($found));
        sort($found);

        return $found;
    }
}
