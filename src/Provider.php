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
     * @var string
     */
    protected $path;

    /**
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        if ( ! file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $this->path = $path;

        return $this;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
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
        $glob = new \FilesystemIterator($this->path, \FilesystemIterator::KEY_AS_FILENAME);
        $list = [];
        foreach ($glob as $name => $file) {
            /* var \SplFileInfo $file*/
            list($database, $connection_name) = self::fileNameDatabaseConnectionNameExt($file);
            $info["name"] = $name;
            $info["database"] = $database;
            $info["connection_name"] = $connection_name;
            $info["filename"] = $file->getPathname();
            $info["ext"] = $file->getExtension();
            $info["size"] = format_bytes($file->getSize());
            $list[] = $info;
        }

        return $list;
    }

    /**
     * @param string|array $sqls
     *
     * @return int|mixed
     */
    public function import($sqls)
    {
        return $this->buildSQL->execute($this->connection, $sqls);
    }

    /**
     * @param $fileName
     *
     * @return array
     */
    public function fileNameDatabaseConnectionNameExt($fileName)
    {
        $path_info = pathinfo($fileName);
        $ret = explode("-", $path_info["basename"]);

        return [$ret[0] ?: "", $ret[1] ?: "", $path_info["extension"] ?: "", $ret[2] ?: ""];
    }

    /**
     * @param $database
     * @param $connectionName
     *
     * @return string
     */
    public function generateFileName($database, $connectionName)
    {
        return $this->generateFullPathFile($database . "-" . $connectionName . "-" . date("YmdHis"));
    }

    /**
     * @param $filename
     *
     * @return string
     */
    public function generateFullPathFile($filename)
    {
        return $this->getPath() . DIRECTORY_SEPARATOR . $filename;
    }
}