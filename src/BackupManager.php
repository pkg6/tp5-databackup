<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup;

use InvalidArgumentException;
use think\App;
use think\db\ConnectionInterface;
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
     * 当前备份的数据库名称.
     *
     * @var string
     */
    protected $database;
    /**
     * 数据库链接别名在,database.php 中connections 中某一个key值
     *
     * @var string
     */
    protected $connectionName;
    /**
     * 当前数据库的配置.
     *
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
     * @var Provider|ProviderInterface
     */
    protected $provider;

    /**
     * @param App $app
     *
     * @throws ClassDefineException
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->setVersion();
        $this->setBuildSQL();
        $this->database();
        $this->setProvider();
    }

    /**
     * 设置sql语句.
     *
     * @param BuildSQLInterface|null $buildSQL
     *
     * @return $this
     *
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
     * @param WriteAbstract $write
     *
     * @return $this
     */
    public function setWrite(WriteAbstract $write)
    {
        $this->writes[$write->ext()] = $write;

        return $this;
    }

    /**
     * @param null|string $type
     *
     * @return WriteAbstract
     *
     * @throws ClassDefineException
     */
    public function getWrite($type = null)
    {
        if (is_null($type)) {
            $type = $this->app->config->get("backup.default", "sql");
        }
        $backups = $this->app->config->get("backup.backups");
        if ( ! isset($backups[$type])) {
            throw new InvalidArgumentException('Undefined backups config:' . $type);
        }
        if ( ! isset($backups[$type]["type"])) {
            throw new InvalidArgumentException('Undefined backups.type config:' . $type);
        }
        $backupclass = $backups[$type]["type"];
        if (class_exists($backupclass)) {
            $backupObject = new $backupclass;
            if ( ! is_subclass_of($backupObject, WriteAbstract::class)) {
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
     * 根据composer.json 设置版本号.
     *
     * @return void
     */
    protected function setVersion()
    {
        $composer = json_decode(file_get_contents($this->app->getRootPath() . "composer.json"), true);
        $this->version = Arr::get($composer, "require.tp5er/tp5-databackup");
    }

    /**
     * 获取版本号.
     *
     * @return string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * 选中需要备份的数据库.
     *
     * @param string $connectionName
     *
     * @return $this
     */
    public function database($connectionName = null)
    {
        if ($connectionName === null) {
            $connectionName = $this->app->config->get("database.default");
        }
        $connections = $this->app->config->get("database.connections");
        if ( ! isset($connections[$connectionName])) {
            throw new InvalidArgumentException('Undefined db config:' . $connectionName);
        }
        $this->databaseConfig = $connections[$connectionName];
        $this->database = Arr::get($this->databaseConfig, "database");
        $this->connectionName = $connectionName;

        return $this;
    }

    /**
     * @param ProviderInterface|null $provider
     *
     * @return $this
     */
    public function setProvider(ProviderInterface $provider = null)
    {
        if (is_null($provider)) {
            $provider = new Provider();

        }
        $this->provider = $provider;
        $path = $this->app
            ->config
            ->get("backup.path", $this->app->getRootPath() . "backup");
        $provider->setPath($path);

        $this->provider->setBuildSQL($this->buildSQL);

        return $this;
    }

    /**
     * @param string|ConnectionInterface $connection
     * 为null的时候读取database.php默认的配置
     * 为字符串时候，读取自定义链接信息
     * 为ConnectionInterface时候就走用户自定义
     * @param string|WriteAbstract $writeType
     *
     * @return ProviderInterface
     *
     * @throws ClassDefineException
     */
    public function provider($connection = null, $writeType = null)
    {
        if (is_null($connection)) {
            $connection = $this->connectionName;
        }
        if (is_string($connection)) {
            $tpConnect = $this->app->get("db")->connect($connection);
            $this->provider->setConnection($tpConnect);
        } elseif (is_subclass_of($connection, ConnectionInterface::class)) {
            $this->provider->setConnection($connection);
        } else {
            $tpConnect = $this->app->get("db");
            $this->provider->setConnection($tpConnect);
        }

        if (is_subclass_of($writeType, WriteAbstract::class)) {
            $this->provider->setWrite($writeType);
        } else {
            $write = $this->getWrite($writeType);
            $this->provider->setWrite($write);
        }

        return $this->provider;
    }

    /**
     * 数据库表列表.
     *
     * @return array
     *
     * @throws ClassDefineException
     */
    public function tables()
    {
        return $this->provider()->tables();
    }

    /**
     * 优化表.
     *
     * @param String|array $tables 表名
     *
     * @throws \Exception
     */
    public function optimize($tables = null)
    {
        return $this->provider()->optimize($tables);
    }

    /**
     * 修复表.
     *
     * @param string|array $tables 表名
     *
     * @throws \Exception
     */
    public function repair($tables)
    {
        return $this->provider()->repair($tables);
    }

    /**
     * 备份第一步.
     *
     * @param string|array $tables 写入的数据表
     *
     * @return bool
     *
     * @throws LockException|ClassDefineException
     */
    public function apiBackupStep1(array $tables)
    {
        $filename = $this->provider->generateFileName($this->database, $this->connectionName);
        $lockKey = "tp5er.backup.task." . crc32($filename . json_encode($tables));
        if ($this->app->cache->has($lockKey)) {
            throw new LockException($lockKey);
        }
        $backup = $this->getWrite();
        $backup->setFileName($filename);

        $this->app->cache->tag("tp5er.backup")->set($lockKey, 1);
        $this->app->cache->tag("tp5er.backup")->set("tp5er.backup.file", $filename);
        $this->app->cache->tag("tp5er.backup")->set("tp5er.backup.tables", $tables);

        return $this->sqlCopyright($backup);
    }

    /**
     * 根据tag清理缓存.
     *
     * @return void
     */
    public function cleanup()
    {
        $this->app->cache->tag("tp5er.backup")->clear();
    }

    /**
     * 备份数据第二步.
     *
     * @param int $index
     * @param int $page
     *
     * @return int
     *
     * @throws BackupStepException
     * @throws ClassDefineException
     * @throws WriteException
     */
    public function apiBackupStep2($index = 0, $page = 0)
    {
        $write = $this->getWrite();
        $filename = $this->app->cache->get("tp5er.backup.file");

        if ( ! $this->app->cache->has("tp5er.backup.file")) {
            throw new BackupStepException(2, "Unable to find file cache");
        }
        $write->setFileName($filename);
        $tables = $this->app->cache->get("tp5er.backup.tables");
        $table = $tables[$index];
        $cahceKey = "tp5er.backup." . $filename . ".page." . $table;
        if ($this->app->cache->has($cahceKey)) {
            $lastPage = $this->writeTableData($write, $table, $page, false);
            if ((int) $lastPage === 0) {
                $this->app->cache->delete($cahceKey);
            } else {
                $this->app->cache->tag("tp5er.backup")->set($cahceKey, $lastPage);
            }

            return $lastPage;
        } else {
            // 首先备份表结构和数据
            $isbackupdata = $this->writeTableStructure($write, $table);
            if ($isbackupdata) {
                $lastPage = $this->writeTableData($write, $table, $page);
                if ((int) $lastPage === 0) {
                    $this->app->cache->delete($cahceKey);
                } else {
                    $this->app->cache->tag("tp5er.backup")->set($cahceKey, $lastPage);
                }

                return $lastPage;
            }
        }

        return 0;
    }

    /**
     * @param $fileName
     *
     * @return mixed
     *
     * @throws ClassDefineException
     */
    public function import($fileName)
    {
        $fileName = $this->provider->generateFullPathFile($fileName);
        if ( ! file_exists($fileName)) {
            throw new FileException($fileName);
        }
        list($_, $connectionName, $ext, $_) = $this->provider->fileNameDatabaseConnectionNameExt($fileName);
        $write = $this->getWrite($ext);
        $sqls = $write->readSQL($fileName);
        $provider = $this->provider($connectionName, $write);

        return $provider->import($sqls);
    }

    /**
     * 获取所有已备份的文件.
     *
     * @return array
     *
     * @throws ClassDefineException
     */
    public function fileList()
    {
        return $this->provider()->files();
    }

    /**
     * 写入表结构.
     *
     * @param WriteAbstract $write
     * @param $table
     *
     * @return mixed
     *
     * @throws WriteException
     */
    protected function writeTableStructure(WriteAbstract $write, $table)
    {
        return $this->provider(null, $write)->writeTableStructure($table);
    }

    /**
     * 备份数据.
     *
     * @param WriteAbstract $write
     * @param $table
     * @param $page
     * @param bool $annotation
     *
     * @return int|mixed
     *
     * @throws ClassDefineException
     * @throws WriteException
     */
    protected function writeTableData(WriteAbstract $write, $table, $page, $annotation = true)
    {
        $limit = $this->app->config->get("backup.limit", 10);

        return $this->provider(null, $write)->writeTableData($table, $limit, $page, $annotation);
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
        $config = $this->getDatabaseConfig();
        $hostname = Arr::get($config, "hostname");
        $hostport = Arr::get($config, "hostport");
        $sql = "-- -----------------------------" . PHP_EOL;
        $sql .= "-- tp5-databackup SQL Dump " . PHP_EOL;
        $sql .= "-- version " . $this->getVersion() . PHP_EOL;
        $sql .= "-- https://github.com/pkg6/tp5-databackup " . PHP_EOL;
        $sql .= "-- " . PHP_EOL;
        $sql .= "-- Host     : " . $hostname . PHP_EOL;
        $sql .= "-- Port     : " . $hostport . PHP_EOL;
        $sql .= "-- Database : " . $this->database . PHP_EOL;
        $sql .= "-- PHP Version : " . phpversion() . PHP_EOL;
        $sql .= "-- Date : " . date("Y-m-d H:i:s") . PHP_EOL;
        $sql .= "-- -----------------------------" . PHP_EOL . PHP_EOL;
        $sql .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL . PHP_EOL;
        $sql .= 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL;

        return $write->writeSQL($sql);
    }

}
