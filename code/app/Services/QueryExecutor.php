<?php

namespace App\Services;

interface QueryExecutor
{
    /** @return mixed */
    public function execute(string $query): mixed;
}

class SqlExecutionException extends \RuntimeException
{
}
