<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * (L) Licensed <https://opensource.org/license/MIT>
 *
 * (A) zhiqiang <https://www.zhiqiang.wang>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\reader;

use think\App;
use think\db\ConnectionInterface;
use think\helper\Arr;
use think\helper\Str;
use tp5er\Backup\BackupInterface;
use tp5er\Backup\exception\SQLExecuteException;
use tp5er\Backup\exception\WriteException;

class Mysql implements ReaderInterface
{
    const NAME = "mysql";
    /**
     * @var App
     */
    protected $app;
    /**
     * @var
     */
    protected $config;

    /**
     * @param App $app
     *
     * @return void
     */
    public function setApp(App $app)
    {
        $this->app = $app;
    }

    /**
     * @param $config
     *
     * @return mixed|void
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @return string
     */
    public function type()
    {
        return self::NAME;
    }

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @param ConnectionInterface $connection
     *
     * @return $this|Mysql
     */
    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param BackupInterface $backup
     *
     * @return string
     */
    public function copyright(BackupInterface $backup)
    {

        $config = $backup->getDatabaseConfig();
        $hostname = Arr::get($config, "hostname");
        $hostport = Arr::get($config, "hostport");
        $sql = "-- -----------------------------" . PHP_EOL;
        $sql .= "-- tp5-databackup SQL Dump " . PHP_EOL;
        $sql .= "-- version " . databackup_version() . PHP_EOL;
        $sql .= "-- https://github.com/pkg6/tp5-databackup " . PHP_EOL;
        $sql .= "-- " . PHP_EOL;
        $sql .= "-- Host     : " . $hostname . PHP_EOL;
        $sql .= "-- Port     : " . $hostport . PHP_EOL;
        $sql .= "-- Database : " . $backup->getDatabase() . PHP_EOL;
        $sql .= "-- PHP Version : " . phpversion() . PHP_EOL;
        $sql .= "-- Date : " . date("Y-m-d H:i:s") . PHP_EOL;
        $sql .= "-- -----------------------------" . PHP_EOL . PHP_EOL;
        $sql .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL . PHP_EOL;
        $sql .= 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL;

        return $sql;
    }

    /**
     * @return array|mixed
     */
    public function tables()
    {
        return $this->connection->query("SHOW TABLE STATUS");
    }

    public function tableCount($table)
    {
        return $this->connection->table($table)->count();
    }

    /**
     * @param $tables
     *
     * @return mixed|string
     */
    public function optimize($tables)
    {
        if (is_array($tables)) {
            $tables = implode('`,`', $tables);
        }

        return $this->connection->query("OPTIMIZE TABLE `{$tables}`");
    }

    /**
     * @param $tables
     *
     * @return mixed
     */
    public function repair($tables)
    {
        if (is_array($tables)) {
            $tables = implode('`,`', $tables);
        }

        return $this->connection->query("REPAIR TABLE `{$tables}`");
    }

    /**
     * @param $table
     *
     * @return array
     *
     * @throws WriteException
     */
    public function tableStructure($table)
    {
        list($isBackupData, $createTableSQL) = $this->executeTableStructure($table);
        $sql = PHP_EOL;
        $sql .= "-- ----------------------------" . PHP_EOL;
        $sql .= "-- Table structure for $table" . PHP_EOL;
        $sql .= "-- ----------------------------" . PHP_EOL;
        $sql .= PHP_EOL;
        $sql .= $createTableSQL;
        $sql .= PHP_EOL;

        return [$sql, $isBackupData];
    }

    /**
     * @param $table
     *
     * @return array
     */
    protected function executeTableStructure($table)
    {
        $result = $this->connection->query("SHOW CREATE TABLE `{$table}`");
        $sql = trim($result[0]['Create Table'] ?? $result[0]['Create View']);
        if ( ! empty($result[0]["Create View"])) {
            return [false, $sql];
        }

        if ( ! Str::endsWith($sql, ';')) {
            $sql .= ' ;';
        }

        return [true, $sql];
    }

    /**
     * @param $table
     * @param $limit
     * @param $page
     * @param bool $annotation
     *
     * @return int|mixed
     *
     * @throws WriteException
     */
    public function tableData($table, $limit, $page, $annotation = true)
    {
        list($lastPage, $instertSQL) = $this->tableInstert(
            $table,
            $page,
            $limit
        );
        // 表示没有数据可以进行备份
        if ($lastPage <= 0) {
            return ["", 0];
        }
        $sql = "";
        if ($annotation) {
            $sql .= "-- ----------------------------" . PHP_EOL;
            $sql .= "-- Records of $table" . PHP_EOL;
            $sql .= "-- ----------------------------" . PHP_EOL;
        }
        $sql .= PHP_EOL;

        //INSERT INTO 开始事务的方式
        //$sql .= "BEGIN;";
        $sql .= $instertSQL;

        //$sql .= "COMMIT;";
        return [$sql, $lastPage];
    }

    protected function tableInstert($table, $page = 0, $limit = 100)
    {
        if ($page <= 0) {
            $page = 1;
        }
        $offset = ($page - 1) * $limit;
        $result = $this->connection->query("SELECT * FROM `{$table}` LIMIT {$limit} OFFSET {$offset}");
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

    /**
     * @param string|array $sqls
     *
     * @return int
     */
    public function import($sqls)
    {
        if (is_array($sqls)) {
            foreach ($sqls as $index => $sql) {
                try {
                    if ($sql != "") {
                        $this->connection->execute($sql);
                    }
                } catch (\Exception $exception) {
                    throw  new SQLExecuteException($index, $sql, $exception);
                }
            }

            return 1;
        }

        return $this->connection->execute($sqls);
    }
}
