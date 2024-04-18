<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup;

use think\db\ConnectionInterface;
use tp5er\Backup\exception\WriteException;

class Provider implements ProviderInterface
{
    /**
     * @var WriteAbstract
     */
    protected $write;
    /**
     * @var ConnectionInterface
     */
    protected $connection;
    /**
     * @var BuildSQLInterface
     */
    protected $buildSQL;
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
     * @return $this|Provider
     */
    public function setWrite(WriteAbstract $write)
    {
        $this->write = $write;

        return $this;
    }

    /**
     * @param ConnectionInterface $connection
     *
     * @return $this|Provider
     */
    public function setConnection(ConnectionInterface $connection)
    {
        $this->connection = $connection;

        return $this;
    }

    /**
     * @param BuildSQLInterface $buildSQL
     *
     * @return $this|Provider
     */
    public function setBuildSQL(BuildSQLInterface $buildSQL)
    {
        $this->buildSQL = $buildSQL;

        return $this;
    }

    /**
     * @return array|mixed
     */
    public function tables()
    {
        return $this->buildSQL->tables($this->connection);
    }

    /**
     * @param $tables
     *
     * @return mixed|string
     */
    public function optimize($tables)
    {
        return $this->buildSQL->optimize($this->connection, $tables);
    }

    /**
     * @param $tables
     *
     * @return mixed
     */
    public function repair($tables)
    {
        return $this->buildSQL->repair($this->connection, $tables);
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
        list($isbackupdata, $createTableSql) = $this->buildSQL->tableStructure($this->connection, $table);

        $sql = PHP_EOL . "--" . PHP_EOL;
        $sql .= "-- 表的结构 `$table`" . PHP_EOL;
        $sql .= "-- " . PHP_EOL;
        $sql .= PHP_EOL;
        $sql .= $createTableSql;
        $sql .= PHP_EOL;
        if ($this->write->writeSQL($sql) == false) {
            throw new WriteException($this->write->getFileName());
        }

        return $isbackupdata;
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
        list($lastPage, $instertSQL) = $this->buildSQL->tableInstert(
            $this->connection,
            $table,
            $page,
            $limit
        );
        if ($lastPage <= 0) {
            return 0;
        }
        $sql = "";
        if ($annotation) {
            $sql .= "--" . PHP_EOL;
            $sql .= "-- 转存表中的数据 `$table`" . PHP_EOL;
            $sql .= "-- " . PHP_EOL;
        }
        $sql .= PHP_EOL . $instertSQL;
        if ($this->write->writeSQL($sql) == false) {
            throw new WriteException($this->write->getFileName());
        }

        return $lastPage;
    }

    /**
     * @return array
     */
    public function files()
    {
        $glob = new \FilesystemIterator($this->filename->getPath(), \FilesystemIterator::KEY_AS_FILENAME);
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
        return $this->buildSQL->execute($this->connection, $sqls);
    }

}
