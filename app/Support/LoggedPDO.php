<?php

namespace App\Support;

use App\Exceptions\SecurityException;
use PDO;
use PDOStatement;

class LoggedPDO extends PDO
{
    /**
     * @var array<int, string>
     */
    public array $log = [];

    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->guardAgainstWriteOperations($query);
        $this->log[] = '['.date('Y-m-d H:i:s').'] [PREPARE] '.$query;

        return parent::prepare($query, $options);
    }

    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $this->guardAgainstWriteOperations($query);
        $this->log[] = '['.date('Y-m-d H:i:s').'] [QUERY] '.$query;

        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }

    protected function guardAgainstWriteOperations(string $query): void
    {
        if (preg_match('/\b(INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER|CREATE)\b/i', $query)) {
            throw new SecurityException('Write operation detected and blocked: '.$query);
        }
    }

    public function getLastQuery(): ?string
    {
        if (empty($this->log)) {
            return null;
        }

        $lastEntry = end($this->log);
        $parts = explode('] ', $lastEntry, 3);

        return $parts[2] ?? null;
    }

    public function saveLogToFile(string $path): void
    {
        $content = implode(PHP_EOL, $this->log).PHP_EOL;
        file_put_contents($path, $content, FILE_APPEND);
    }

    public function clearLog(): void
    {
        $this->log = [];
    }
}
