<?php

namespace App\Services;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class SqlServerSnapshotService
{
    public function tables(Connection $connection): array
    {
        $schema = match ($connection->getDriverName()) {
            'sqlsrv' => 'dbo',
            'mysql', 'mariadb' => $connection->getDatabaseName(),
            default => null,
        };

        return array_column($connection->getSchemaBuilder()->getTables($schema), 'name');
    }

    public function snapshot(Connection $connection): array
    {
        $tables = [];
        foreach ($this->tables($connection) as $table) {
            $tables[$table] = [
                'columns' => $connection->getSchemaBuilder()->getColumnListing($table),
                'rows' => $connection->table($table)->get()->map(fn ($row) => (array) $row)->all(),
            ];
        }

        return [
            'format' => 'paolo-paolo-database-snapshot',
            'version' => 1,
            'created_at' => now()->toIso8601String(),
            'tables' => $tables,
        ];
    }

    public function write(Connection $connection, string $path): void
    {
        File::put($path, json_encode($this->snapshot($connection),
            JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public function restoreFile(Connection $connection, string $path): void
    {
        $snapshot = json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($snapshot)) {
            throw new InvalidArgumentException('Invalid application database snapshot.');
        }
        $this->restore($connection, $snapshot);
    }

    public function restore(Connection $connection, array $snapshot, bool $requireAllTables = true, bool $requireEmpty = false): void
    {
        $driver = $connection->getDriverName();
        if (!in_array($driver, ['sqlsrv', 'mysql', 'mariadb'])) {
            throw new InvalidArgumentException('This restore requires a SQL Server or MySQL database.');
        }
        if (($snapshot['format'] ?? null) !== 'paolo-paolo-database-snapshot'
            || ($snapshot['version'] ?? null) !== 1
            || !is_array($snapshot['tables'] ?? null) || $snapshot['tables'] === []) {
            throw new InvalidArgumentException('Invalid application database snapshot.');
        }

        $tableNames = $this->tables($connection);
        $currentTables = array_combine(array_map('strtolower', $tableNames), $tableNames);
        $incomingTables = array_map('strtolower', array_keys($snapshot['tables']));
        if (count($incomingTables) !== count(array_unique($incomingTables))
            || array_diff($incomingTables, array_keys($currentTables))
            || ($requireAllTables && array_diff(array_keys($currentTables), $incomingTables))) {
            throw new InvalidArgumentException('The backup tables do not match the application database.');
        }

        $validatedTables = [];
        foreach ($snapshot['tables'] as $name => $data) {
            $table = $currentTables[strtolower($name)];
            $columns = $connection->getSchemaBuilder()->getColumnListing($table);
            // Backups made before category archiving restore those categories as active.
            if (strtolower($table) === 'tbl_category' && is_array($data)
                && is_array($data['columns'] ?? null) && is_array($data['rows'] ?? null)
                && array_values(array_diff($columns, $data['columns'])) === ['Is_Archived']
                && !array_diff($data['columns'], $columns)) {
                foreach ($data['rows'] as &$row) {
                    if (is_array($row)) {
                        if (array_key_exists('Is_Archived', $row)) {
                            throw new InvalidArgumentException('Invalid legacy category backup row.');
                        }
                        $row['Is_Archived'] = false;
                    }
                }
                unset($row);
                $data['columns'][] = 'Is_Archived';
            }
            if (!is_array($data) || !is_array($data['columns'] ?? null) || !is_array($data['rows'] ?? null)
                || $data['columns'] === []
                || count(array_filter($data['columns'], 'is_string')) !== count($data['columns'])
                || count($data['columns']) !== count(array_unique($data['columns']))
                || array_diff($data['columns'], $columns)
                || ($requireAllTables && array_diff($columns, $data['columns']))) {
                throw new InvalidArgumentException("The backup columns do not match {$table}.");
            }
            foreach ($data['rows'] as $row) {
                if (!is_array($row) || array_diff(array_keys($row), $data['columns'])
                    || array_diff($data['columns'], array_keys($row))) {
                    throw new InvalidArgumentException("Invalid backup row for {$table}.");
                }
                foreach ($row as $value) {
                    if ($value !== null && !is_scalar($value)) {
                        throw new InvalidArgumentException("Invalid backup value for {$table}.");
                    }
                }
            }
            if ($requireEmpty && $connection->table($table)->exists()) {
                throw new InvalidArgumentException("Import stopped: {$table} already contains data.");
            }
            $hasIdentity = collect($connection->getSchemaBuilder()->getColumns($table))
                ->contains(fn ($column) => $column['auto_increment']);
            $validatedTables[$table] = ['columns' => $data['columns'], 'rows' => $data['rows'], 'identity' => $hasIdentity];
        }

        $connection->transaction(function () use ($connection, $validatedTables, $driver) {
            $grammar = $connection->getQueryGrammar();
            if ($driver === 'sqlsrv') {
                foreach ($validatedTables as $table => $data) {
                    $connection->statement('ALTER TABLE '.$grammar->wrapTable($table).' NOCHECK CONSTRAINT ALL');
                }
            } else {
                $connection->statement('SET FOREIGN_KEY_CHECKS = 0');
            }

            foreach ($validatedTables as $table => $data) {
                $connection->table($table)->delete();
                if ($data['rows'] === []) {
                    continue;
                }
                $wrappedTable = $grammar->wrapTable($table);
                if ($driver === 'sqlsrv' && $data['identity']) {
                    $connection->unprepared("SET IDENTITY_INSERT {$wrappedTable} ON");
                }
                try {
                    // SQL Server accepts at most 2,100 parameters per statement.
                    $chunkSize = max(1, min(1000, intdiv(2000, count($data['columns']))));
                    foreach (array_chunk($data['rows'], $chunkSize) as $rows) {
                        $connection->table($table)->insert($rows);
                    }
                } finally {
                    if ($driver === 'sqlsrv' && $data['identity']) {
                        $connection->unprepared("SET IDENTITY_INSERT {$wrappedTable} OFF");
                    }
                }
            }

            if ($driver === 'sqlsrv') {
                foreach ($validatedTables as $table => $data) {
                    $connection->statement('ALTER TABLE '.$grammar->wrapTable($table).' WITH CHECK CHECK CONSTRAINT ALL');
                }
            } else {
                $connection->statement('SET FOREIGN_KEY_CHECKS = 1');
            }
        });
    }
}
