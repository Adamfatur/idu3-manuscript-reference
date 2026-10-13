<?php

namespace App\Services;

interface LlmEvaluator
{
    /**
     * @return array{decision:string,score:float|int,feedback:string}
     */
    public function evaluate(string $qs, string $qe, mixed $os): array;
}
