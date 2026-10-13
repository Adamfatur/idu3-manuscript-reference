<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $path = dirname(__DIR__) . '/code/app/' . $relative . '.php';
    if (is_file($path)) {
        require $path;
    }
});

use App\Helpers\TierHelper;
use App\Services\AffectivePersona;
use App\Services\GamificationBoard;
use App\Services\LlmEvaluator;
use App\Services\QueryExecutor;
use App\Services\RewardAllocator;
use App\Services\SandboxSQLService;
use App\Services\SqlExecutionException;

$failures = 0;

function check(bool $condition, string $message): void
{
    global $failures;
    if ($condition) {
        echo "ok  {$message}\n";
        return;
    }
    $failures++;
    fwrite(STDERR, "FAIL {$message}\n");
}

$sandbox = new SandboxSQLService(['students', 'courses'], 'u5_');
check(
    $sandbox->prepare('SELECT * FROM students') === 'SELECT * FROM u5_students',
    'user 5 prefix rewrites students to u5_students'
);
check(
    $sandbox->prepare("SELECT * FROM students WHERE name = 'students'") === "SELECT * FROM u5_students WHERE name = 'students'",
    'a table name inside a string literal stays as written'
);
check(
    $sandbox->prepare('SELECT * FROM Students') === 'SELECT * FROM u5_Students',
    'the prefix keeps the case of the table name'
);
check(
    $sandbox->prepare("SELECT 'drop' FROM students") === "SELECT 'drop' FROM u5_students",
    'a forbidden word inside a string is not a DDL command'
);

foreach (['DROP TABLE students', 'truncate courses', 'ALTER TABLE students', 'create table x (id int)', 'GRANT ALL ON students TO u', 'revoke all on courses from u'] as $blocked) {
    $threw = false;
    try {
        $sandbox->prepare($blocked);
    } catch (RuntimeException) {
        $threw = true;
    }
    check($threw, "blocked: {$blocked}");
}

check(
    $sandbox->prepare('SELECT created_at FROM students') === 'SELECT created_at FROM u5_students',
    'created_at is not the CREATE command'
);

check(TierHelper::DECAY_POINTS === [1 => 100, 2 => 90, 3 => 75], 'decay points are 100, 90, 75');
foreach (TierHelper::DECAY_FACTOR as $attempt => $factor) {
    check(TierHelper::BASE_POINTS * $factor === (float) TierHelper::DECAY_POINTS[$attempt], "attempt {$attempt} points follow Pbase × D(n)");
}

$tiers = [
    [0.0, ['Beginner', 'Sobat Syntax Error']],
    [20.0, ['Beginner', 'Sobat Syntax Error']],
    [20.9, ['Beginner', 'Sobat Syntax Error']],
    [21.0, ['Apprentice', 'Kuli Database']],
    [40.0, ['Apprentice', 'Kuli Database']],
    [41.0, ['Intermediate', 'Pawang Error']],
    [60.0, ['Intermediate', 'Pawang Error']],
    [61.0, ['Advanced', 'Kang Inner Join']],
    [80.0, ['Advanced', 'Kang Inner Join']],
    [81.0, ['Expert', 'Raja Query']],
    [90.0, ['Expert', 'Raja Query']],
    [90.9, ['Expert', 'Raja Query']],
    [91.0, ['Elite', 'Suhu Database']],
    [99.0, ['Elite', 'Suhu Database']],
    [99.9, ['Elite', 'Suhu Database']],
    [100.0, ['Legendary', 'Si Paling Sepuh']],
];
foreach ($tiers as [$rate, $expected]) {
    check(TierHelper::getTier($rate) === $expected, "tier at {$rate}");
}

$rows = [
    (object) ['status' => 'approved', 'attempts_used' => 1],
    (object) ['status' => 'approved', 'attempts_used' => 2],
    (object) ['status' => 'approved', 'attempts_used' => 3],
    (object) ['status' => 'rejected', 'attempts_used' => 1],
];
check(TierHelper::calculateSuccessRate($rows, 3) === 88.3, 'success rate uses only approved decay points');

$prompt = AffectivePersona::prompt();
check(str_contains($prompt, 'Never mention that you are an AI or an automated system.'), 'persona never names itself as AI');
check(str_contains($prompt, 'Identical output BUT query does NOT follow instructions -> REJECT'), 'persona rejects coincidental correctness');
check(AffectivePersona::MODEL === 'Google Gemini 2.0 Flash', 'model name from section 3.3');
check(AffectivePersona::requestBody()['generationConfig']['temperature'] === 0.1, 'temperature 0.1');
check(AffectivePersona::requestBody()['generationConfig']['maxOutputTokens'] === 2048, 'maxOutputTokens 2048');

$board = new GamificationBoard();
$allocator = new RewardAllocator($board, ['students', 'courses']);

$matching = new class implements QueryExecutor {
    public function execute(string $query): mixed
    {
        return [['name' => 'Sari']];
    }
};
$unusedLlm = new class implements LlmEvaluator {
    public int $calls = 0;

    public function evaluate(string $qs, string $qe, mixed $os): array
    {
        $this->calls++;

        return ['decision' => 'approved', 'score' => 100, 'feedback' => 'should not run'];
    }
};
$result = $allocator->allocate(
    ['user' => 'E01', 'query' => 'SELECT name FROM students', 'attempts_used' => 2],
    ['expected_sql' => 'SELECT name FROM students'],
    $matching,
    $unusedLlm,
    [],
);
check(abs($result['score'] - 90) < 1e-9 && $result['decision'] === 'approved' && $result['feedback'] === null, 'fast path on attempt 2 scores 90 and has no feedback text');
check($unusedLlm->calls === 0, 'fast path does not call the LLM');
check(abs(($board->rate('E01') ?? -1) - 3.2) < 1e-9, 'attempt 2 on one of 28 challenges is a 3.2 success rate');
check($board->tier('E01') === ['Beginner', 'Sobat Syntax Error'], 'one approved challenge of 28 stays on the first tier');
check($board->rank('E01') === 1, 'leaderboard records the approved user');

$llm = new class implements LlmEvaluator {
    public int $calls = 0;

    public function evaluate(string $qs, string $qe, mixed $os): array
    {
        $this->calls++;

        return ['decision' => 'approved', 'score' => 85, 'feedback' => 'Look at the join condition.'];
    }
};
$suspicious = $allocator->allocate(
    ['user' => 'E02', 'query' => 'SELECT name FROM courses', 'attempts_used' => 2],
    ['expected_sql' => 'SELECT name FROM students'],
    $matching,
    $llm,
    [],
);
check($llm->calls === 1, 'identical output from a different table goes to the LLM');
check(abs($suspicious['score'] - 76.5) < 1e-9 && $suspicious['decision'] === 'approved', 'an LLM score is then multiplied by D(n)');
check($suspicious['feedback'] === 'Look at the join condition.', 'LLM feedback is kept on approval');

$rejector = new class implements LlmEvaluator {
    public function evaluate(string $qs, string $qe, mixed $os): array
    {
        return ['decision' => 'rejected', 'score' => 40, 'feedback' => 'The filter does not match the question.'];
    }
};
$differentOutput = new class implements QueryExecutor {
    public function execute(string $query): mixed
    {
        return str_contains(strtolower($query), 'from courses') ? [['name' => 'Other']] : [['name' => 'Sari']];
    }
};
$rejected = $allocator->allocate(
    ['user' => 'E03', 'query' => 'SELECT name FROM courses', 'attempts_used' => 1],
    ['expected_sql' => 'SELECT name FROM students'],
    $differentOutput,
    $rejector,
    [],
);
check($rejected['score'] === 0 && $rejected['decision'] === 'rejected', 'a rejected review scores 0');
check($board->rate('E03') === null, 'a rejection does not move the tier');

$broken = new class implements QueryExecutor {
    public function execute(string $query): mixed
    {
        if (str_contains($query, 'FROM missing')) {
            throw new SqlExecutionException('Unknown table missing');
        }

        return [];
    }
};
$errored = $allocator->allocate(
    ['user' => 'E04', 'query' => 'SELECT * FROM missing', 'attempts_used' => 1],
    ['expected_sql' => 'SELECT * FROM students'],
    $broken,
    $unusedLlm,
    [],
);
check($errored === ['score' => 0, 'decision' => 'rejected', 'feedback' => 'Unknown table missing'], 'a query error is rejected before the LLM');

if ($failures > 0) {
    fwrite(STDERR, "{$failures} failed\n");
    exit(1);
}

echo "All reference checks passed\n";
