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

namespace tp5er\Backup;

use InvalidArgumentException;
use think\App;
use think\db\ConnectionInterface;
use think\helper\Arr;
use tp5er\Backup\build\BuildSQLInterface;
use tp5er\Backup\build\Mysql;
use tp5er\Backup\exception\BackupStepException;
use tp5er\Backup\exception\ClassDefineException;
use tp5er\Backup\exception\FileNotException;
use tp5er\Backup\exception\LockException;
use tp5er\Backup\exception\WriteException;
use tp5er\Backup\provider\Provider;
use tp5er\Backup\provider\ProviderInterface;
use tp5er\Backup\write\SQLFileWrite;
use tp5er\Backup\write\WriteAbstract;

class BackupManager implements BackupInterface
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
     * 当前备份哪一个表.
     *
     * @var string
     */
    protected $currentBackupTable = "";

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
     * @return FileName
     */
    public function getFileNameObject()
    {
        $fileName = new FileName($this, $this->app);

        return $fileName;
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
    public function getWriteObject($writeType = null)
    {
        if (is_null($writeType)) {
            $writeType = $this->app->config->get("backup.default", "sql");
        }
        $backups = $this->app->config->get("backup.backups");
        if ( ! isset($backups[$writeType])) {
            throw new InvalidArgumentException('Undefined backups config:' . $writeType);
        }
        if ( ! isset($backups[$writeType]["type"])) {
            throw new InvalidArgumentException('Undefined backups.type config:' . $writeType);
        }
        $backupclass = $backups[$writeType]["type"];
        if (class_exists($backupclass)) {
            $writeObject = new $backupclass;
            if ( ! is_subclass_of($writeObject, WriteAbstract::class)) {
                throw new ClassDefineException($backupclass, WriteAbstract::class);
            }
        } elseif (isset($this->writes[$backupclass])) {
            $writeObject = new $this->writes[$backupclass];
        }
        if (isset($writeObject)) {
            $writeObject->setApp($this->app);
            $writeObject->setManager($this);

            return $writeObject;
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
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
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
        $this->provider->setFileName($this->getFileNameObject());
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
    public function getProviderObject($connection = null, $writeType = null)
    {
        if (is_null($connection)) {
            $connection = $this->connectionName;
        }
        $provider = $this->provider;
        if (is_string($connection)) {
            $provider->setConnection($this->app->get("db")->connect($connection));
        } elseif (is_subclass_of($connection, ConnectionInterface::class)) {
            $provider->setConnection($connection);
        } else {
            $provider->setConnection($this->app->get("db"));
        }

        if (is_subclass_of($writeType, WriteAbstract::class)) {
            $provider->setWrite($writeType);
        } else {
            $provider->setWrite($this->getWriteObject($writeType));
        }

        return $provider;
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
        return $this->getProviderObject()->tables();
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
        return $this->getProviderObject()->optimize($tables);
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
        return $this->getProviderObject()->repair($tables);
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
    public function backupStep1(array $tables)
    {
        $filenameObject = $this->getFileNameObject();
        $filename = $filenameObject->generateFileName($this->database, $this->connectionName);
        $lockKey = Cache::LockPrefix . crc32($filename . json_encode($tables));
        if ($this->app->cache->has($lockKey)) {
            throw new LockException($lockKey);
        }
        $write = $this->getWriteObject();
        $write->setFileName($filename);

        Cache::set($this->app, $lockKey, 1);
        Cache::set($this->app, Cache::File, $filename);
        Cache::set($this->app, Cache::Tables, $tables);

        return $filenameObject->copyright($write);
    }

    /**
     * 当前备份的文件名称.
     *
     * @return string
     */
    public function getCurrentBackupFile()
    {
        return $this->app->cache->get(Cache::File);
    }

    /**
     * 可作为备份第一步，用于前端进度条
     *
     * @param array $tables
     * @return array
     *
     * @throws BackupStepException
     * @throws ClassDefineException
     * @throws LockException
     */
    public function tableCounts($tables)
    {
        $limit = $this->getLimit();
        $filenameObject = $this->getFileNameObject();
        $filename = $filenameObject->generateFileName($this->database, $this->connectionName);
        $lockKey = Cache::LockPrefix . crc32($filename . json_encode($tables));
        if ($this->app->cache->has($lockKey)) {
            throw new LockException($lockKey);
        }

        $write = $this->getWriteObject();
        $write->setFileName($filename);

        $ret = [];
        $count_sum = 0;
        $steps_sum = 0;
        $data = [];
        foreach ($tables as $k => $table) {
            $count = $this->getProviderObject()->tableCount($table);
            $count_sum += $count;
            //当前表的总数量
            $info["count"] = $count;
            //根据配置得到需要一次性备份多少条
            $info["limit"] = $limit;
            //需要请求多少次第二步接口
            $steps = ceil($count / $limit) + 1;
            $steps_sum += $steps;
            $info["steps"] = $steps;
            $data[$k] = $info;

        }
        $ret["count"] = $count_sum;
        $ret["steps"] = $steps_sum;
        $ret["list"] = $data;
        if ( ! $filenameObject->copyright($write)) {
            throw new BackupStepException(1, "File write failure");
        }
        Cache::set($this->app, $lockKey, 2);
        Cache::set($this->app, Cache::TableCounts, $ret);
        Cache::set($this->app, Cache::File, $filename);
        Cache::set($this->app, Cache::Tables, $tables);

        return $ret;

    }

    /**
     * 备份数据第二步.
     *
     * @param int $index
     * @param int $page
     *
     * @return int
     *  当page <=0 表示该表已备份完毕
     *  当page > 0 表示该表需要继续进行备份
     *
     * @throws BackupStepException
     * @throws ClassDefineException
     * @throws WriteException
     */
    public function backupStep2($index = 0, $page = 0)
    {
        $filename = $this->app->cache->get(Cache::File);
        if ( ! $this->app->cache->has(Cache::File)) {
            throw new BackupStepException(2, "Unable to find file cache");
        }

        $write = $this->getWriteObject();
        $write->setFileName($filename);

        $tables = $this->app->cache->get(Cache::Tables);
        // 没有表可以进行备份
        if ( ! isset($tables[$index])) {
            return OPT::backupPageTableDoesNotExist;
        }
        $this->currentBackupTable = $tables[$index];

        $cahceKey = Cache::Table . $filename . "-" . $this->getCurrentBackupTable();
        if ($this->app->cache->has($cahceKey)) {
            // 有此缓存在标识我认为你已经备份完表结果以及部分数据
            $lastPage = $this->writeTableData($write, $this->getCurrentBackupTable(), $page, false);
            if ((int) $lastPage === 0) {
                $this->app->cache->delete($cahceKey);
            } else {
                Cache::set($this->app, $cahceKey, $lastPage);
            }

            return $lastPage;
        } else {
            // 首先进行备份表结果，然后判断是否进行备份表数据
            $isBackupdata = $this->writeTableStructure($write, $this->getCurrentBackupTable());
            if ($isBackupdata) {
                $lastPage = $this->writeTableData($write, $this->getCurrentBackupTable(), $page);
                if ((int) $lastPage === 0) {
                    $this->app->cache->delete($cahceKey);
                } else {
                    Cache::set($this->app, $cahceKey, $lastPage);
                }

                return $lastPage;
            }
        }

        return OPT::backupPageTableOver;
    }

    /**
     * 当前备份正在执行的表.
     *
     * @return string
     */
    public function getCurrentBackupTable()
    {
        return $this->currentBackupTable;
    }

    /**
     * 备份表数据.
     *
     * @param $tables
     *
     * @return array
     *
     * @throws ClassDefineException
     * @throws LockException
     */
    public function backup($tables)
    {
        $ret = [];
        //任务创建成功
        if ($this->backupStep1($tables)) {
            //备份所有表数据
            foreach ($tables as $index => $table) {
                //我相信在数据库中不会出现表重复表名吧
                $lastPage = $this->backupAllData($index);
                if ($lastPage === 0) {
                    $ret[$table] = true;
                } else {
                    $ret[$table] = false;
                }
            }
        }
        $this->cleanup();

        return $ret;
    }

    /**
     * 备份所有表数据.
     *
     * @param $index
     * @param $page
     *
     * @return int
     *
     * @throws BackupStepException
     * @throws ClassDefineException
     * @throws WriteException
     */
    protected function backupAllData($index = 0, $page = 1)
    {
        //任务创建成功
        $lastPage = $this->backupStep2($index, $page);
        if ($lastPage > 0) {
            return $this->backupAllData($index, $lastPage);
        }

        return $lastPage;
    }

    /**
     * 根据tag清理缓存.
     *
     * @return void
     */
    public function cleanup()
    {
        Cache::clear($this->app);
    }

    /**
     * 获取所有已备份的文件.
     *
     * @return FileInfo[]
     *
     * @throws ClassDefineException
     */
    public function files()
    {
        return $this->getProviderObject()->files();
    }

    /**
     * @param $fileName
     *
     * @return int
     *
     * @throws ClassDefineException
     */
    public function import($fileName)
    {
        $fileName = $this->getFileNameObject()->generateFullPathFile($fileName);
        if ( ! file_exists($fileName)) {
            throw new FileNotException($fileName);
        }
        list($_, $_, $ext, $_) = $this->getFileNameObject()->fileNameDatabaseConnectionNameExt($fileName);
        $write = $this->getWriteObject($ext);
        $sqls = $write->readSQL($fileName);
        $provider = $this->getProviderObject(null, $write);

        return $provider->import($sqls);
    }

    /**
     * 写入表结构.
     *
     * @param WriteAbstract $write
     * @param $table
     *
     * @return mixed
     *
     * @throws WriteException|ClassDefineException
     */
    protected function writeTableStructure(WriteAbstract $write, $table)
    {
        return $this->getProviderObject(null, $write)->writeTableStructure($table);
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
        $limit = $this->getLimit();

        return $this->getProviderObject(null, $write)->writeTableData($table, $limit, $page, $annotation);
    }

    /**
     * @return array
     */
    public function getDatabaseConfig()
    {
        return $this->databaseConfig;
    }

    /**
     * @return array|mixed
     */
    protected function getLimit()
    {
        return $this->app->config->get("backup.limit", 100);
    }

}
