<?php

namespace tp5er\Backup\reader;

use think\db\ConnectionInterface;
use tp5er\Backup\format\SQLFormat;

class Mysql
{
    protected ConnectionInterface $connection;

    public function __construct(ConnectionInterface $connection)
    {
        $this->connection = $connection;
    }

    public function header(string $database, string $connectionName): string
    {
        return SQLFormat::header($database, $connectionName);
    }

    public function tables(): array
    {
        return $this->connection->query('SHOW TABLE STATUS');
    }

    public function tableCount(string $table): int
    {
        return $this->connection->table($table)->count();
    }

    public function tableStructure(string $table, bool $withDrop = false): array
    {
        $result = $this->connection->query("SHOW CREATE TABLE `{$table}`");

        if (!empty($result[0]['Create View'])) {
            $createSql = trim($result[0]['Create View']);

            return [SQLFormat::tableStructure($table, $createSql, $withDrop), false];
        }

        $createSql = trim($result[0]['Create Table']);

        return [SQLFormat::tableStructure($table, $createSql, $withDrop), true];
    }

    public function tableData(string $table, int $limit, int $offset): string
    {
        $rows = $this->connection->query("SELECT * FROM `{$table}` LIMIT {$limit} OFFSET {$offset}");
        if (empty($rows)) {
            return '';
        }

        return SQLFormat::tableInsert($table, $rows);
    }

    public function import($sqls): bool
    {
        $pdo = $this->connection->connect();
        if (is_array($sqls)) {
            foreach ($sqls as $sql) {
                if ($sql !== '') {
                    $pdo->exec($sql);
                }
            }

            return true;
        }

        return $pdo->exec($sqls) !== false;
    }

    public function optimize($tables)
    {
        if (is_array($tables)) {
            $tables = implode('`,`', $tables);
        }

        return $this->connection->query("OPTIMIZE TABLE `{$tables}`");
    }

    public function repair($tables)
    {
        if (is_array($tables)) {
            $tables = implode('`,`', $tables);
        }

        return $this->connection->query("REPAIR TABLE `{$tables}`");
    }
}
