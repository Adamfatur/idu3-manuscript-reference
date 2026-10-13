<?php

namespace App\Services;

/**
 * Multi-tenant sandbox from Listing 2 and Section 3.5.
 * Each known table is rewritten with a per-user prefix. Destructive DDL is refused
 * before that rewrite. Words inside string literals are left unchanged.
 */
class SandboxSQLService
{
    public const FORBIDDEN = ['drop', 'truncate', 'alter', 'create', 'grant', 'revoke'];

    /** @param list<string> $knownTables */
    public function __construct(
        private readonly array $knownTables,
        private readonly string $tablePrefix,
    ) {
    }

    public function prepare(string $sql): string
    {
        $this->assertExecutable($sql);

        return $this->prefixTableNames($sql);
    }

    public function strippedSql(string $sql): string
    {
        [$stripped] = $this->stripStringLiterals($sql);

        return $stripped;
    }

    public function assertExecutable(string $sql): void
    {
        [$stripped] = $this->stripStringLiterals($sql);
        foreach (self::FORBIDDEN as $word) {
            if (preg_match('/\b' . preg_quote($word, '/') . '\b/i', $stripped) === 1) {
                throw new \RuntimeException('Destructive DDL is blocked: ' . $word);
            }
        }
    }

    protected function prefixTableNames(string $sql): string
    {
        [$stripped, $placeholders] = $this->stripStringLiterals($sql);
        $tables = $this->knownTables;
        usort($tables, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
        foreach ($tables as $table) {
            $stripped = preg_replace(
                '/\b(' . preg_quote($table, '/') . ')\b/i',
                $this->tablePrefix . '$1',
                $stripped
            );
        }

        return $this->restoreStringLiterals($stripped, $placeholders);
    }

    /**
     * @return array{0:string,1:array<string,string>}
     */
    private function stripStringLiterals(string $sql): array
    {
        $placeholders = [];
        $out = '';
        $length = strlen($sql);
        $i = 0;
        while ($i < $length) {
            $quote = $sql[$i];
            if ($quote !== "'" && $quote !== '"') {
                $out .= $quote;
                $i++;
                continue;
            }
            $j = $i + 1;
            while ($j < $length) {
                if ($sql[$j] === '\\' && ($j + 1) < $length) {
                    $j += 2;
                    continue;
                }
                if ($sql[$j] === $quote) {
                    $doubled = ($j + 1) < $length && $sql[$j + 1] === $quote;
                    if ($doubled) {
                        $j += 2;
                        continue;
                    }
                    break;
                }
                $j++;
            }
            $end = $j < $length ? $j + 1 : $length;
            $token = "\x00S" . count($placeholders) . "\x00";
            $placeholders[$token] = substr($sql, $i, $end - $i);
            $out .= $token;
            $i = $end;
        }

        return [$out, $placeholders];
    }

    /** @param array<string,string> $placeholders */
    private function restoreStringLiterals(string $sql, array $placeholders): string
    {
        return strtr($sql, $placeholders);
    }
}
