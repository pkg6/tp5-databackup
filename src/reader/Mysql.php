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
    
    /**
     * 获取表的 AUTO_INCREMENT 值
     * @param string $table 表名
     * @return int AUTO_INCREMENT 值
     */
    public function getAutoIncrement(string $table): int
    {
        $result = $this->connection->query("SHOW TABLE STATUS LIKE '{$table}'");
        if (!empty($result) && isset($result[0]['Auto_increment'])) {
            return intval($result[0]['Auto_increment']);
        }
        return 0;
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
    /**
     * @param $tables
     *
     * @return mixed|string
     */
    public function truncate($tables)
    {
        if (!is_array($tables)) {
            $tables = explode('`,`', $tables);
        }
         foreach ($tables as $table) {
         $this->connection->query("TRUNCATE TABLE `{$table}`");
        } 
         return true;
    }


    /**
     * @param $tables
     *
     * @return mixed
     */
    public function drop($tables)
    {
        if (!is_array($tables)) {
            $tables = explode('`,`', $tables);
        }
         foreach ($tables as $table) {
         $this->connection->query("DROP TABLE `{$table}`");
        } 
         return true;
    }
    
    /**
     * 批量修改表前缀
     *
     * @param array|string $tables
     * @param string $prefix
     *
     * @return bool
     */
    public function prefixChange($tables, $prefix)
    {
        if (!is_array($tables)) {
            $tables = explode(',', $tables);
        }
        
        $successCount = 0;
        $failCount = 0;
        
        foreach ($tables as $table) {
            $table = trim($table);
            if (empty($table)) continue;
            
            // 获取表名中前缀之后的部分（找到第一个下划线）
            // 例如：tp_user -> user，然后新表名是 prefix + user
            $pos = strpos($table, '_');
            if ($pos !== false) {
                // 提取下划线之后的部分
                $tableName = substr($table, $pos + 1);
                $newTableName = $prefix . $tableName;
            } else {
                // 没有下划线，直接使用前缀 + 原表名
                $newTableName = $prefix . $table;
            }
            
            try {
                $sql = "RENAME TABLE `{$table}` TO `{$newTableName}`;";
                $this->connection->query($sql);
                $successCount++;
            } catch (\Exception $e) {
                $failCount++;
            }
        }
        
        // 返回修改成功的数量，而不是仅判断是否全部成功
        return $successCount;
    }

}
