<?php

namespace tp5er\Backup\build;

use PDO;
use think\db\ConnectionInterface;
use think\db\PDOConnection;
use tp5er\Backup\BuildSQLInterface;

class Mysql implements BuildSQLInterface
{
    /**
     * @param ConnectionInterface $connection
     * @return array
     */
    public function tables(ConnectionInterface $connection)
    {
        return $connection->query("SHOW TABLE STATUS");
    }

    /**
     * @param ConnectionInterface $connection
     * @param $table
     * @return string
     */
    public function optimize(ConnectionInterface $connection, $table = null)
    {
        if (is_array($table)) {
            $table = implode('`,`', $table);
        }
        return $connection->query("OPTIMIZE TABLE `{$table}`");
    }

    /**
     * @param ConnectionInterface $connection
     * @param $table
     * @return mixed
     */
    public function repair(ConnectionInterface $connection, $table = null)
    {
        if (is_array($table)) {
            $table = implode('`,`', $table);
        }
        return $connection->query("REPAIR TABLE `{$table}`");
    }

    /**
     * @param ConnectionInterface $connection
     * @param $table
     * @return array
     */
    public function tableStructure(ConnectionInterface $connection, $table)
    {
        $result = $connection->query("SHOW CREATE TABLE `{$table}`");

        $sql = trim($result[0]['Create Table'] ?? $result[0]['Create View']);
        if (!empty($result[0]["Create View"])) {
            return [false, $sql];
        }
        return [true, $sql];
    }

    /**
     * @param ConnectionInterface $connection
     * @param $table
     * @param $limitStart
     * @return array
     */
    public function tableInstert(ConnectionInterface $connection, $table, $offset = 0)
    {
        if ($offset <= 0) {
            $offset = 1;
        }
        $maxLimit = 1;
        $result   = $connection->query("SELECT * FROM `{$table}` LIMIT {$maxLimit} OFFSET {$offset}");
        if (count($result) == 0) {
            return [0, ""];
        }
        $tableFieldArr = $this->tableField($result[0]);
        $sql           = "INSERT INTO `{$table}` (" . implode(",", $tableFieldArr) . ") VALUES ";
        $tableDataArr  = [];
        foreach ($result as &$row) {
            foreach ($row as &$val) {
                if (is_numeric($val)) {
                } else if (is_null($val)) {
                    $val = 'NULL';
                } else {
                    $val = "'" . str_replace(array("\r", "\n"), array('\\r', '\\n'), addslashes($val)) . "'";
                }
            }
            $tableDataArr[] = PHP_EOL . "(" . implode(", ", array_values($row)) . ")";
        }
        $sql .= implode(",", $tableDataArr);
        return [$offset + 1, $sql];
    }

    /**
     * @param ConnectionInterface $connection
     * @param $sql
     * @return int
     */
    public function execute(ConnectionInterface $connection, $sql)
    {
        return $connection->execute($sql);
    }

    /**
     * @param array $field
     * @return array
     */
    protected function tableField(array $field)
    {
        $sqlArr = [];
        foreach ($field as $f => $v) {
            $sqlArr[$f] = "`{$f}`";
        }
        return $sqlArr;
    }

}