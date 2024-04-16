<?php

namespace tp5er\Backup;

use InvalidArgumentException;
use think\App;
use think\helper\Arr;
use tp5er\Backup\build\Mysql;
use tp5er\Backup\exception\BackupStepException;
use tp5er\Backup\exception\ClassDefineException;
use tp5er\Backup\exception\FileException;
use tp5er\Backup\exception\LockException;
use tp5er\Backup\exception\WriteException;
use tp5er\Backup\write\SQLFileWrite;


class BackupManager
{
    /**
     * @var string
     */
    protected $version = "";
    /**
     * @var App
     */
    protected $app;

    /**
     * @var BuildSQLInterface
     */
    protected $buildSQL;
    /**
     * 当前备份的数据库名称
     * @var string
     */
    protected $database;
    /**
     * 数据库链接别名在,database.php 中connections 中某一个key值
     * @var string
     */
    protected $connectionName;
    /**
     * 当前数据库的配置
     * @var array
     */
    protected $databaseConfig = [];


    /**
     * @var string[]
     */
    public $writes = [
        SQLFileWrite::ext => SQLFileWrite::class,
    ];

    /**
     * @param App $app
     * @throws ClassDefineException
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->setVersion();
        $this->setBuildSQL();
        $this->database();
    }

    /**
     * 设置sql语句
     * @param BuildSQLInterface|null $buildSQL
     * @return $this
     * @throws ClassDefineException
     */
    public function setBuildSQL(BuildSQLInterface $buildSQL = null)
    {
        if ($buildSQL === null) {
            $sqlClass = $this->app->config->get("backup.build", Mysql::class);
            if (is_subclass_of($sqlClass, BuildSQLInterface::class)) {
                $buildSQL = new $sqlClass;
            } else {
                throw new ClassDefineException($sqlClass, BuildSQLInterface::class);
            }
        }
        $this->buildSQL = $buildSQL;
        return $this;
    }

    /**
     * @param WriteAbstract $backup
     * @return $this
     */
    public function setWrite(WriteAbstract $write)
    {
        $this->writes[$write->ext()] = $write;
        return $this;
    }

    /**
     * @param null $type
     * @return WriteAbstract
     * @throws ClassDefineException
     */
    public function getWrite($type = null)
    {
        if (is_null($type)) {
            $type = $this->app->config->get("backup.default", "sql");
        }
        $backups = $this->app->config->get("backup.backups");
        if (!isset($backups[$type])) {
            throw new InvalidArgumentException('Undefined backups config:' . $type);
        }
        if (!isset($backups[$type]["type"])) {
            throw new InvalidArgumentException('Undefined backups.type config:' . $type);
        }
        $backupclass = $backups[$type]["type"];
        if (class_exists($backupclass)) {
            $backupObject = new $backupclass;
            if (!is_subclass_of($backupObject, WriteAbstract::class)) {
                throw new ClassDefineException($backupclass, WriteAbstract::class);
            }
        } elseif (isset($this->writes[$backupclass])) {
            $backupObject = new $this->writes[$backupclass];
        }
        if (isset($backupObject)) {
            $backupObject->setApp($this->app);
            $backupObject->setManager($this);
            return $backupObject;
        }
        throw new InvalidArgumentException("Unable to find {$backupclass} write method");
    }

    /**
     * 根据composer.json 设置版本号
     * @return void
     */
    protected function setVersion()
    {
        $composer      = json_decode(file_get_contents($this->app->getRootPath() . "composer.json"), true);
        $this->version = Arr::get($composer, "require.tp5er/tp5-databackup");
    }

    /**
     * 获取版本号
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * 选中需要备份的数据库
     * @param string $connectionName
     * @return $this
     */
    public function database($connectionName = null)
    {
        if ($connectionName === null) {
            $connectionName = $this->app->config->get("database.default");
        }
        $connections = $this->app->config->get("database.connections");
        if (isset($connections[$connectionName])) {
            $this->databaseConfig = $connections[$connectionName];
        } else {
            throw new InvalidArgumentException('Undefined db config:' . $connectionName);
        }
        $this->database       = Arr::get($this->databaseConfig, "database");
        $this->connectionName = $connectionName;
        return $this;
    }

    /**
     * @param null $connectionName
     * @return \think\db\ConnectionInterface
     */
    protected function DB($connectionName = null)
    {
        if (is_null($connectionName)) {
            $connectionName = $this->connectionName;
        }
        return $this->app->get("db")->connect($connectionName);
    }

    /**
     * 数据库表列表
     * @return array
     */
    public function tables()
    {
        return $this->buildSQL->tables($this->DB());
    }

    /**
     * 优化表
     * @param String $tables 表名
     * @throws \Exception
     */
    public function optimize($tables = null)
    {
        return $this->buildSQL->optimize($this->DB(), $tables);
    }

    /**
     * 修复表
     * @param String $tables 表名
     * @throws \Exception
     */
    public function repair($tables = null)
    {
        return $this->buildSQL->repair($this->DB(), $tables);
    }

    /**
     * @return array
     */
    public function getDatabaseConfig()
    {
        return $this->databaseConfig;
    }

    /**
     * @return bool
     */
    protected function sqlCopyright(WriteAbstract $write)
    {
        $config   = $this->getDatabaseConfig();
        $hostname = Arr::get($config, "hostname");
        $hostport = Arr::get($config, "hostport");
        $sql      = "-- -----------------------------" . PHP_EOL;
        $sql      .= "-- tp5-databackup SQL Dump " . PHP_EOL;
        $sql      .= "-- version " . $this->getVersion() . PHP_EOL;
        $sql      .= "-- https://github.com/pkg6/tp5-databackup " . PHP_EOL;
        $sql      .= "-- " . PHP_EOL;
        $sql      .= "-- Host     : " . $hostname . PHP_EOL;
        $sql      .= "-- Port     : " . $hostport . PHP_EOL;
        $sql      .= "-- Database : " . $this->database . PHP_EOL;
        $sql      .= "-- PHP Version : " . phpversion() . PHP_EOL;
        $sql      .= "-- Date : " . date("Y-m-d H:i:s") . PHP_EOL;
        $sql      .= "-- -----------------------------" . PHP_EOL . PHP_EOL;
        $sql      .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL;
        $sql      .= 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL . PHP_EOL;
        return $write->writeSQL($sql);
    }

    /**
     * 写入表结构
     * @param WriteAbstract $write
     * @param $table
     * @return mixed
     * @throws WriteException
     */
    public function writeTableStructure(WriteAbstract $write, $table)
    {
        list($isbackupdata, $createTableSql) = $this->buildSQL->tableStructure($this->DB(), $table);
        $sql = "--" . PHP_EOL;
        $sql .= "-- 表的结构 `$table`" . PHP_EOL;
        $sql .= "-- " . PHP_EOL;
        $sql .= PHP_EOL;
        $sql .= $createTableSql;
        $sql .= PHP_EOL;
        if ($write->writeSQL($sql) == false) {
            throw new WriteException($write->getFileName());
        }
        return $isbackupdata;
    }

    /**
     * 备份数据
     * @param WriteAbstract $write
     * @param $table
     * @param $offset
     * @param bool $annotation
     * @return int|mixed
     * @throws WriteException
     */
    public function writeTableData(WriteAbstract $write, $table, $offset, $annotation = true)
    {

        $limit = $this->app->config->get("backup.limit", 100);
        list($lastOffset, $instertSQL) = $this->buildSQL->tableInstert(
            $this->DB(),
            $table,
            $offset,
            $limit
        );
        if ($lastOffset <= 0) {
            return 0;
        }
        $sql = "";
        if ($annotation) {
            $sql .= "--" . PHP_EOL;
            $sql .= "-- 转存表中的数据 `$table`" . PHP_EOL;
            $sql .= "-- " . PHP_EOL;
        }
        $sql .= $instertSQL;
        if ($write->writeSQL($sql) == false) {
            throw new WriteException($write->getFileName());
        }
        return $lastOffset;
    }


    /**
     * 备份第一步
     * @param string|array $tables 写入的数据表
     * @return bool
     * @throws LockException|ClassDefineException
     */
    public function apiBackupStep1(array $tables)
    {
        $filename = $this->fullFileName();
        $lock     = crc32($filename . json_encode($tables)) . ".lock";
        if (is_file($lock)) {
            throw new LockException($lock);
        } else {
            //创建锁文件
            file_put_contents($lock, time());
        }
        $backup = $this->getWrite();
        $backup->setFileName($filename);
        $this->app->cache->set("tp5er.backup.lock", $lock);
        $this->app->cache->set("tp5er.backup.file", $filename);
        $this->app->cache->set("tp5er.backup.tables", $tables);
        return $this->sqlCopyright($backup);
    }

    /**
     * 备份数据第二步
     * @param int $index
     * @param int $offset
     * @return int
     * @throws BackupStepException
     * @throws ClassDefineException
     * @throws WriteException
     */
    public function apiBackupStep2($index = 0, $offset = 0)
    {
        $write    = $this->getWrite();
        $filename = $this->app->cache->get("tp5er.backup.file");

        if (!$this->app->cache->has("tp5er.backup.file")) {
            throw new BackupStepException(2, "Unable to find file cache");
        }
        $write->setFileName($filename);
        $tables   = $this->app->cache->get("tp5er.backup.tables");
        $table    = $tables[$index];
        $cahceKey = "tp5er.backup." . $filename . ".offset." . $table;
        if ($this->app->cache->has($cahceKey)) {
            $lastOffset = $this->writeTableData($write, $table, $offset, false);
            $this->app->cache->set($cahceKey, $lastOffset);
            return $lastOffset;
        } else {
            // 首先备份表结构和数据
            $isbackupdata = $this->writeTableStructure($write, $table);
            if ($isbackupdata) {
                $lastOffset = $this->writeTableData($write, $table, $offset);
                $this->app->cache->set($cahceKey, $lastOffset);
                return $lastOffset;
            }
        }
        return 0;
    }


    /**
     * @param $fileName
     * @return mixed
     * @throws ClassDefineException
     */
    public function import($fileName)
    {
        $fileName = $this->filename($fileName);
        if (!file_exists($fileName)) {
            throw new FileException($fileName);
        }
        $write              = $this->getWrite(pathinfo($fileName, PATHINFO_EXTENSION));
        $database_type_time = explode("-", pathinfo($fileName, PATHINFO_BASENAME));
        $sql                = $write->readSQL($fileName);
        // 操作数据库
        $connectionName = $database_type_time[1];
        return $this->buildSQL->execute($this->DB($connectionName), $sql);
    }


    /**
     * 获取所有已备份的文件
     * @return array
     */
    public function fileList()
    {
        $flag = \FilesystemIterator::KEY_AS_FILENAME;
        $glob = new \FilesystemIterator($this->path(), $flag);
        $list = array();
        foreach ($glob as $name => $file) {
            /** var \SplFileInfo $file*/
            $database_type_time      = explode("-", $name);
            $info["name"]            = $name;
            $info["database"]        = $database_type_time[0];
            $info["connection_name"] = $database_type_time[1];
            $info["filename"]        = $file->getPathname();
            $info["ext"]             = $file->getExtension();
            $info["size"]            = format_bytes($file->getSize());
            $list[]                  = $info;
        }
        return $list;
    }


    /**
     * @return string
     */
    protected function fullFileName()
    {
        return $this->filename($this->database .
            "-" .
            $this->connectionName .
            "-" .
            date("YmdHis"));
    }


    /**
     * @param $filename
     * @return string
     */
    protected function filename($filename)
    {
        return $this->path() .
            DIRECTORY_SEPARATOR .
            $filename;
    }


    /**
     * @return array|mixed
     */
    public function path()
    {
        $path = $this->app->config->get("backup.path", $this->app->getRootPath() . "backup");
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
        return $path;
    }
}