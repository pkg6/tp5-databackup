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
use tp5er\Backup\BackupInterface;
use tp5er\Backup\exception\SQLExecuteException;
use tp5er\Backup\exception\WriteException;
use tp5er\Backup\format\SQLFormat;

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
     * @return void
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
        return SQLFormat::copyright($backup);
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
        $sql = SQLFormat::tableStructure($table, $createTableSQL, Arr::get($this->config, 'drop_sql', false));
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
        $sql = SQLFormat::executeTableStructure($result);
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
     * @return array
     */
    public function tableData($table, $limit, $page, $annotation = true)
    {
        list($lastPage, $instertSQL) = $this->tableInsert(
            $table,
            $page,
            $limit
        );
        // 表示没有数据可以进行备份
        if ($lastPage <= 0) {
            return ["", 0];
        }
        $sql = SQLFormat::tableData($table, $instertSQL, $annotation);

        return [$sql, $lastPage];
    }

    protected function tableInsert($table, $page = 0, $limit = 100)
    {
        if ($page <= 0) {
            $page = 1;
        }
        $offset = ($page - 1) * $limit;
        $result = $this->connection->query("SELECT * FROM `{$table}` LIMIT {$limit} OFFSET {$offset}");
        $sql = SQLFormat::tableInsert($table, $result);
        if ($sql == "") {
            return [0, ""];
        }

        return [$page + 1, $sql];
    }

    /**
     * @param string|array $sqls
     *
     * @return int
     *
     * @throws SQLExecuteException
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
