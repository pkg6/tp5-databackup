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

namespace tp5er\Backup\provider;

use think\db\ConnectionInterface;
use tp5er\Backup\exception\SQLExecuteException;
use tp5er\Backup\exception\WriteException;
use tp5er\Backup\FileInfo;
use tp5er\Backup\FileName;
use tp5er\Backup\write\WriteAbstract;

class MysqlProvider implements ProviderInterface
{
    /**
     * @return string
     */
    public function type()
    {
        return "mysql";
    }

    /**
     * @var WriteAbstract
     */
    protected $write;
    /**
     * @var ConnectionInterface
     */
    protected $connection;
    /**
     * @var FileName
     */
    protected $filename;

    /**
     * @param FileName $fileName
     *
     * @return $this
     */
    public function setFileName(FileName $fileName)
    {
        $this->filename = $fileName;

        return $this;
    }

    /**
     * @param WriteAbstract $write
     *
     * @return $this|MysqlProvider
     */
    public function setWrite(WriteAbstract $write)
    {
        $this->write = $write;

        return $this;
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return $this|MysqlProvider
     */
    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @return string
     */
    public function initSQL()
    {
        $sql = 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL . PHP_EOL;
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
     * @return mixed
     *
     * @throws WriteException
     */
    public function writeTableStructure($table)
    {
        list($isbackupdata, $createTableSql) = $this->tableStructure($table);
        $sql = PHP_EOL;
        $sql .= "-- ----------------------------" . PHP_EOL;
        $sql .= "-- Table structure for $table" . PHP_EOL;
        $sql .= "-- ----------------------------" . PHP_EOL;
        $sql .= PHP_EOL;
        $sql .= $createTableSql;
        $sql .= PHP_EOL;
        if ($this->write->writeSQL($sql) == false) {
            throw new WriteException($this->write->getFileName());
        }

        return $isbackupdata;
    }

    /**
     * @param ConnectionInterface $connection
     * @param $table
     *
     * @return array
     */
    protected function tableStructure($table)
    {
        $result = $this->connection->query("SHOW CREATE TABLE `{$table}`");
        $sql = trim($result[0]['Create Table'] ?? $result[0]['Create View']);
        if ( ! empty($result[0]["Create View"])) {
            return [false, $sql];
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
    public function writeTableData($table, $limit, $page, $annotation = true)
    {
        list($lastPage, $instertSQL) = $this->tableInstert(
            $table,
            $page,
            $limit
        );
        if ($lastPage <= 0) {
            return 0;
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

        if ($this->write->writeSQL($sql) == false) {
            throw new WriteException($this->write->getFileName());
        }

        return $lastPage;
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
     * @return FileInfo[]
     */
    public function files()
    {
        $glob = new \FilesystemIterator(
            $this->filename->getPath(),
            \FilesystemIterator::KEY_AS_FILENAME
        );
        $list = [];
        foreach ($glob as $file) {
            /* var \SplFileInfo $file*/
            $list[] = $this->filename->SplFileInfo($file);
        }

        return $list;
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
