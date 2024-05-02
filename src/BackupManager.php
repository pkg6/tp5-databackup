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
use tp5er\Backup\exception\BackupStepException;
use tp5er\Backup\exception\LockException;
use tp5er\Backup\reader\Mysql;
use tp5er\Backup\reader\ReaderInterface;
use tp5er\Backup\writer\SQLFileWriter;
use tp5er\Backup\writer\WriterInterface;

class BackupManager implements BackupInterface
{

    /**
     * @var App
     */
    protected $app;

    protected $config = [
        "default" => "file",
        "backups" => [
            "file" => [
                //目前只支持sql文件
                "write_type" => 'file',
                //读取生成sql语句的类
                "reader_type" => 'mysql',
                //sql文件存储路径
                "path" => "./backup",
            ]
        ],
        //一次请求存储100条数据
        "limit" => 100,
    ];
    /**
     * @var array|\ArrayAccess|mixed
     */
    protected $version;
    /**
     * @var array
     */
    protected $databaseConfig;
    /**
     * @var string
     */
    protected $database;
    /**
     * @var string
     */
    protected $connectionName;

    /**
     * @var ConnectionInterface
     */
    protected $connection;

    /**
     * @var string[]
     */
    public $writers = [
//        "sql" => new SQLFileWriter::class,
    ];

    /**
     * @var array
     */
    protected $readers = [
//        "mysql" => new Mysql::class,
//        "mongo"  => "",
//        "oracle" => "",
//        "pgsql"  => "",
//        "sqlite" => "",
//        "sqlsrv" => "",
    ];
    /**
     * @var string
     */
    protected $currentReaderType;
    /**
     * @var string
     */
    protected $currentWriteType;
    /**
     * @var mixed
     */
    protected $currentBackupTable;

    /**
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->setVersion();

        $this->database();
        //设置默认的
        $this->setWriter(new SQLFileWriter());
        $this->setReader(new Mysql());
        $this->config = array_merge($this->config, $this->app->config->get("backup"));
    }

    /**
     * @return void
     */
    protected function setVersion()
    {
        $composer = json_decode(file_get_contents($this->app->getRootPath() . "composer.json"), true);
        $this->version = Arr::get($composer, "require.tp5er/tp5-databackup");
    }

    /**
     * @return array|\ArrayAccess|mixed|string
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param $name
     *
     * @return array
     */
    public function config($name = null)
    {
        if (is_null($name)) {
            $name = Arr::get($this->config, "default", "file");
        }
        $backups = Arr::get($this->config, "backups");
        if (empty($backups[$name])) {
            throw new InvalidArgumentException('Undefined backups config:' . $name);
        }

        return $backups[$name];
    }

    /**
     * @param WriterInterface $writer
     *
     * @return $this
     */
    public function setWriter(WriterInterface $writer)
    {
        $this->writers[$writer->type()] = $writer;

        return $this;
    }

    /**
     * @param $writeType
     *
     * @return WriterInterface
     */
    public function getWriter($writeType = null)
    {
        if (is_null($writeType)) {
            $config = $this->config();
            $writeType = Arr::get($config, "write_type", "file");
        }
        if (class_exists($writeType)) {
            if (is_subclass_of($writeType, WriterInterface::class)) {
                $this->setWriter($writeType);
            }
        }
        if (isset($this->writers[$writeType])) {
            $this->currentWriteType = $writeType;
            $writer = new     $this->writers[$writeType];

            return $writer;
        }
        throw new InvalidArgumentException("Unable to find {$writeType} writer method");
    }

    /**
     * @return string
     */
    public function getCurrentWriterType()
    {
        return $this->currentWriteType;
    }

    /**
     * @param ReaderInterface|null $reader
     *
     * @return $this
     */
    public function setReader(ReaderInterface $reader = null)
    {
        $this->readers[$reader->type()] = $reader;

        return $this;
    }

    /**
     * @param $readerType
     *
     * @return ReaderInterface
     */
    public function getReader($readerType = null)
    {
        if (is_null($readerType)) {
            $config = $this->config();
            $readerType = Arr::get($config, "reader_type", "mysql");
        }
        if (class_exists($readerType)) {
            if (is_subclass_of($readerType, ReaderInterface::class)) {
                $this->setReader($readerType);
            }
        }
        if (isset($this->readers[$readerType])) {
            $this->currentReaderType = $readerType;

            $writer = $this->readers[$readerType];

            return $writer;
        }
        throw new InvalidArgumentException("Unable to find {$readerType} reader method");
    }

    /**
     * @return string
     */
    public function getCurrentReaderType()
    {
        return $this->currentReaderType;
    }

    /**
     * @param $connectionName
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
        $this->connection = $this->app->get("db")->connect($this->connectionName);

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
     * @return array
     */
    public function getDatabaseConfig()
    {
        return $this->databaseConfig;
    }

    /**
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connectionName;
    }

    /**
     * @return ConnectionInterface
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @param $databaseName
     * @param $writeType
     * @param $readerType
     *
     * @return Factory
     */
    public function factory($name = null, $writeType = null, $readerType = null)
    {
        $write = $this->getWriter($writeType);
        $reader = $this->getReader($readerType);

        return new Factory(
            $this->app,
            $this,
            $this->getConnection(),
            $write,
            $reader,
            $this->config($name)
        );
    }

    /**
     * @return array
     */
    public function tables()
    {
        return $this->factory()->getReader()->tables();
    }

    /**
     * @param $tables
     *
     * @return mixed|void
     */
    public function optimize($tables = null)
    {
        return $this->factory()->getReader()->optimize($tables);
    }

    /**
     * @param $tables
     *
     * @return mixed|void
     */
    public function repair($tables)
    {
        return $this->factory()->getReader()->repair($tables);
    }

    /**
     * @param array $tables
     *
     * @return mixed
     *
     * @throws LockException
     */
    public function backupStep1(array $tables)
    {
        $factory = $this->factory();
        $writer = $factory->getWriter();
        $filename = $writer->generateFileName();
        $lockKey = Cache::LockPrefix . crc32($filename . json_encode($tables));
        if ($this->app->cache->has($lockKey)) {
            throw new LockException($lockKey);
        }
        Cache::set($this->app, $lockKey, 1);
        Cache::set($this->app, Cache::File, $filename);
        Cache::set($this->app, Cache::Tables, $tables);

        return $writer->writeSQL($factory->getReader()->copyright($this));
    }

    /**
     * @return string|void
     */
    public function getCurrentBackupFile()
    {
        return $this->app->cache->get(Cache::File);
    }

    /**
     * 可作为备份第一步，用于前端进度条
     *
     * @param $tables
     *
     * @return array
     *
     * @throws BackupStepException
     * @throws LockException
     */
    public function tableCounts($tables)
    {
        $limit = $this->getLimit();
        $factory = $this->factory();
        $writer = $factory->getWriter();
        $filename = $writer->generateFileName();

        $lockKey = Cache::LockPrefix . crc32($filename . json_encode($tables));
        if ($this->app->cache->has($lockKey)) {
            throw new LockException($lockKey);
        }
        $ret = [];
        $count_sum = 0;
        $steps_sum = 0;
        $data = [];
        foreach ($tables as $k => $table) {
            $count = $factory->getReader()->tableCount($table);
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
        Cache::set($this->app, $lockKey, 2);
        Cache::set($this->app, Cache::TableCounts, $ret);
        Cache::set($this->app, Cache::File, $filename);
        Cache::set($this->app, Cache::Tables, $tables);
        if ( ! $writer->writeSQL($factory->getReader()->copyright($this))) {
            throw new BackupStepException(1, "File write failure");
        }

        return $ret;
    }

    /**
     * @param $index
     * @param $page
     *
     * @return mixed|void
     *
     * @throws BackupStepException
     */
    public function backupStep2($index = 0, $page = 0)
    {
        if ( ! $this->app->cache->has(Cache::File)) {
            throw new BackupStepException(2, "Unable to find file cache");
        }
        $filename = $this->getCurrentBackupFile();
        $factory = $this->factory();

        $tables = $this->app->cache->get(Cache::Tables);

        // 没有表可以进行备份
        if ( ! isset($tables[$index])) {
            return OPT::backupPageTableDoesNotExist;
        }

        $limit = $this->getLimit();
        $this->currentBackupTable = $tables[$index];

        $cahceKey = Cache::Table . $filename . "-" . $this->getCurrentBackupTable();

        if ($this->app->cache->has($cahceKey)) {
            list($dataSQL, $lastPage) = $factory->getReader()
                ->tableData(
                    $this->getCurrentBackupTable(),
                    $limit,
                    $page,
                    false
                );
            if ($lastPage > 0) {
                $factory->getWriter()->writeSQL($dataSQL);
                Cache::set($this->app, $cahceKey, $lastPage);
            } else {
                $this->app->cache->delete($cahceKey);
            }

            return $lastPage;
        } else {
            // 首先进行备份表结果，然后判断是否进行备份表数据
            list($sql, $isBackupData) = $factory->getReader()->tableStructure($this->getCurrentBackupTable());

            $factory->getWriter()->writeSQL($sql);
            if ($isBackupData) {
                list($dataSQL, $lastPage) = $factory->getReader()->tableData(
                    $this->getCurrentBackupTable(),
                    $limit,
                    $page
                );
                $factory->getWriter()->writeSQL($dataSQL);
                if ($lastPage <= 0) {
                    $this->app->cache->delete($cahceKey);
                } else {
                    Cache::set($this->app, $cahceKey, $lastPage);
                }

                return $lastPage;
            }
        }

        return OPT::backupPageTableOver;

    }

    protected function getLimit()
    {
        return Arr::get($this->config, "limit", 100);
    }

    /**
     * @return string
     */
    public function getCurrentBackupTable()
    {
        return $this->currentBackupTable;
    }

    /**
     * @param $tables
     *
     * @return mixed|void
     */
    public function backup($tables)
    {
        $ret = [];
        if ($this->backupStep1($tables)) {
            foreach ($tables as $index => $table) {
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
     * @return void
     */
    public function cleanup()
    {
        Cache::clear($this->app);
    }

    /**
     * @return
     */
    public function files()
    {
        return $this->factory()->getWriter()->files();
    }

    /**
     * @param $fileName
     *
     * @return mixed
     */
    public function import($fileName)
    {
        $factory = $this->factory();
        $sqls = $factory->getWriter()->readSQL($fileName);

        return $factory->getReader()->import($sqls);
    }
}
