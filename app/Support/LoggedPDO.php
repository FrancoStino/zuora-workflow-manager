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

    /**
     * Prepares a SQL statement after verifying it contains no disallowed write operations and logs the query with a timestamp.
     *
     * @param string $query The SQL query to prepare.
     * @param array $options Options passed to PDO::prepare.
     * @return PDOStatement|false PDOStatement on success, `false` on failure.
     * @throws \App\Exceptions\SecurityException If the query contains a prohibited write operation (e.g., INSERT, UPDATE, DELETE, DROP, TRUNCATE, ALTER, CREATE).
     */
    public function prepare(string $query, array $options = []): PDOStatement|false
    {
        $this->guardAgainstWriteOperations($query);
        $this->log[] = '['.date('Y-m-d H:i:s').'] [PREPARE] '.$query;

        return parent::prepare($query, $options);
    }

    /**
     * Execute an SQL query while logging the query with a timestamp and enforcing write-operation protection.
     *
     * This method records the query with a `[QUERY]` tag, invokes a security guard that blocks write operations,
     * and then delegates execution to the parent PDO::query implementation.
     *
     * @throws App\Exceptions\SecurityException If the query contains a prohibited write operation.
     * @return PDOStatement|false The resulting PDOStatement on success, or `false` on failure.
     */
    public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false
    {
        $this->guardAgainstWriteOperations($query);
        $this->log[] = '['.date('Y-m-d H:i:s').'] [QUERY] '.$query;

        return parent::query($query, $fetchMode, ...$fetchModeArgs);
    }

    /**
     * Prevent execution of SQL write operations by detecting write-operation keywords.
     *
     * @param string $query The SQL query to inspect for potentially dangerous write statements.
     * @throws \App\Exceptions\SecurityException If the query contains `INSERT`, `UPDATE`, `DELETE`, `DROP`, `TRUNCATE`, `ALTER`, or `CREATE`.
     */
    protected function guardAgainstWriteOperations(string $query): void
    {
        if (preg_match('/\b(INSERT|UPDATE|DELETE|DROP|TRUNCATE|ALTER|CREATE)\b/i', $query)) {
            throw new SecurityException('Write operation detected and blocked: '.$query);
        }
    }

    /**
     * Retrieves the SQL text of the most recently logged query.
     *
     * Returns the recorded query string from the last log entry, or `null` if the log is empty or the last entry cannot be parsed.
     *
     * @return string|null The SQL of the last logged entry, or `null` when unavailable.
     */
    public function getLastQuery(): ?string
    {
        if (empty($this->log)) {
            return null;
        }

        $lastEntry = end($this->log);
        $parts = explode('] ', $lastEntry, 3);

        return $parts[2] ?? null;
    }

    /**
     * Appends the in-memory query log to a file at the given path.
     *
     * Each log entry is written on its own line and a trailing newline is added.
     *
     * @param string $path Filesystem path to the file where log entries will be appended.
     */
    public function saveLogToFile(string $path): void
    {
        $content = implode(PHP_EOL, $this->log).PHP_EOL;
        file_put_contents($path, $content, FILE_APPEND);
    }

    /**
     * Clears the in-memory query log.
     *
     * Removes all entries stored in the instance's `log` property.
     */
    public function clearLog(): void
    {
        $this->log = [];
    }
}