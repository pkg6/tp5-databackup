<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\build;

use think\db\ConnectionInterface;
use tp5er\Backup\BuildSQLInterface;

class Mysql implements BuildSQLInterface
{
    /**
     * @param ConnectionInterface $connection
     *
     * @return array
     */
    public function tables(ConnectionInterface $connection)
    {
        return $connection->query("SHOW TABLE STATUS");
    }

    /**
     * @param ConnectionInterface $connection
     * @param $table
     *
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
     *
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
     *
     * @return array
     */
    public function tableStructure(ConnectionInterface $connection, $table)
    {
        $result = $connection->query("SHOW CREATE TABLE `{$table}`");

        $sql = trim($result[0]['Create Table'] ?? $result[0]['Create View']);
        if ( ! empty($result[0]["Create View"])) {
            return [false, $sql];
        }

        return [true, $sql];
    }

    /**
     * @param ConnectionInterface $connection
     * @param $table
     * @param int $page
     * @param int $limit
     *
     * @return array
     */
    public function tableInstert(ConnectionInterface $connection, $table, $page = 0, $limit = 100)
    {
        if ($page <= 0) {
            $page = 1;
        }
        $offset = ($page - 1) * $limit;
        $result = $connection->query("SELECT * FROM `{$table}` LIMIT {$limit} OFFSET {$offset}");
        if (count($result) == 0) {
            return [0, ""];
        }
        $tableFieldArr = $this->tableField($result[0]);
        $sql = "INSERT INTO `{$table}` (" . implode(",", $tableFieldArr) . ") VALUES ";
        $tableDataArr = [];
        foreach ($result as &$row) {
            foreach ($row as &$val) {
                if (is_numeric($val)) {
                } elseif (is_null($val)) {
                    $val = 'NULL';
                } else {
                    $val = "'" . str_replace(["\r", "\n"], ['\\r', '\\n'], addslashes($val)) . "'";
                }
            }
            $tableDataArr[] = PHP_EOL . "(" . implode(", ", array_values($row)) . ")";
        }
        $sql .= implode(",", $tableDataArr);

        return [$page + 1, $sql];
    }

    /**
     * @param ConnectionInterface $connection
     * @param $sql
     *
     * @return int
     */
    public function execute(ConnectionInterface $connection, $sql)
    {
        return $connection->execute($sql);
    }

    /**
     * @param array $field
     *
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
