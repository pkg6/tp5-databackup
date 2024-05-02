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

namespace tp5er\Backup\writer;

use think\App;
use think\helper\Arr;
use tp5er\Backup\BackupInterface;
use tp5er\Backup\exception\FileNotException;
use tp5er\Backup\OPT;

class SQLFileWriter implements WriterInterface
{

    /**
     * @return string
     */
    public function type()
    {
        return "file";
    }

    /**
     * @var App
     */
    protected $app;
    /**
     * @var
     */
    protected $config;
    /**
     * @var BackupInterface
     */
    protected $backup;

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
     * @param BackupInterface $backup
     *
     * @return mixed|void
     */
    public function setBackup(BackupInterface $backup)
    {
        $this->backup = $backup;
    }

    /**
     * @return string
     */
    public function generateFileName()
    {
        //2.1版本
        //database-databaseName-20240430104103.sql
        //2.2版本
        // database-databaseName-write_type-reader_type-version-time.sql
        $name = sprintf(
            "%s-%s-%s-%s-%s-%s.%s",
            $this->backup->getDatabase(),
            $this->backup->getConnectionName(),
            $this->backup->getCurrentWriterType(),
            $this->backup->getCurrentReaderType(),
            BackupInterface::version,
            date("YmdHis"),
            OPT::SQLFileWriterExt
        );

        return $this->path($name);
    }

    /**
     * @param $fileName
     *
     * @return array|void
     */
    protected function explodeFileName($fileName)
    {
        $path_info = pathinfo($fileName);
        $filenames = explode("-", $path_info["basename"]);

        if (count($filenames) <= 1) {
            return;
        }
        $database = $filenames[0];
        $connectionName = $filenames[1];
        $ext = $path_info["extension"];

        if (count($filenames) < 5) {
            //老版本的文件命名方式
            $writerType = "";
            $system_name = $filenames[2];
        } else {
            //新版本的命令方式
            $system_name = $filenames[5] ?? "";
            $writerType = $filenames[2];
        }
        $readerType = $filenames[3] ?? "";
        $version = $filenames[4] ?? "";

        return [
            $database,
            $connectionName,
            $ext,
            $system_name,

            $writerType,
            $readerType,
            $version,

        ];

    }

    /**
     * @param \SplFileInfo $fileInfo
     *
     * @return array
     */
    protected function splFileInfo(\SplFileInfo $fileInfo)
    {
        $name = $fileInfo->getFilename();
        list(
            $database,
            $connectionName,
            $ext,
            $system_name,
            $writerType,
            $readerType,
            $version,
        ) = $this->explodeFileName($name);
        $info = [];
        $info["name"] = $name;
        $info["database"] = $database;
        $info["connection"] = $connectionName;
        $info["filename"] = $fileInfo->getPathname();
        $info["system_name"] = $system_name;
        $info["write_type"] = $writerType;
        $info["reader_type"] = $readerType;
        $info["version"] = $version;
        $info["ext"] = $ext;
        $info["size"] = format_bytes($fileInfo->getSize());

        return $info;
    }

    /**
     * @return array|void
     */
    public function files()
    {
        $list = [];
        $glob = new \FilesystemIterator(
            $this->path(),
            \FilesystemIterator::KEY_AS_FILENAME
        );
        /* @var \SplFileInfo $file */
        foreach ($glob as $file) {
            if ($file->isFile()) {
                if ($file->getExtension() != OPT::SQLFileWriterExt) {
                    continue;
                }
                $list[] = $this->SplFileInfo($file);
            }
        }

        return $list;
    }

    /**
     * @param string $sql
     *
     * @return bool
     */
    public function writeSQL(string $sql)
    {
        $filename = $this->backup->getCurrentBackupFile();
        $result = file_put_contents(
            $filename,
            $sql . PHP_EOL,
            LOCK_EX | FILE_APPEND
        );
        if ($result) {
            clearstatcache();

            return true;
        }

        return false;
    }

    public function filename($filename)
    {
        $info = pathinfo($filename);
        if ($info["dirname"] === ".") {
            return $this->path(pathinfo($filename, PATHINFO_BASENAME));
        }

        return $filename;
    }

    /**
     * @param $file
     *
     * @return array|false|string[]
     */
    public function readSQL($file)
    {
        $pathFileName = $this->filename($file);
        if ( ! file_exists($pathFileName)) {
            throw new FileNotException($pathFileName);
        }
        $sql = file_get_contents($pathFileName);
        $sqlArr = explode(PHP_EOL . PHP_EOL, $sql);

        return $sqlArr;
    }

    /**
     * @return string
     */
    protected function path($fileName = null)
    {
        $path = Arr::get(
            $this->config,
            "path",
            $this->app->getRootPath() . "backup"
        );
        mkdirs($path);
        if (is_null($fileName)) {
            return $path;
        }

        return $path . DIRECTORY_SEPARATOR . $fileName;
    }

}
