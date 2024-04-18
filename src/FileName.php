<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup;

use think\App;
use think\helper\Arr;

class FileName
{

    /**
     * @var BackupManager
     */
    protected $manager;

    /**
     * @param BackupManager $manager
     * @param App $app
     */
    public function __construct(BackupManager $manager, App $app)
    {
        $this->manager = $manager;
        $this->app = $app;
        $path = $this->app
            ->config
            ->get("backup.path", $this->app->getRootPath() . "backup");
        $this->setPath($path);
    }

    /**
     * @param string $path
     *
     * @return $this
     */
    protected function setPath($path)
    {
        if ( ! file_exists($path)) {
            mkdir($path, 0755, true);
        }
        $this->path = $path;

        return $this;
    }

    /**
     * @param \SplFileInfo $file
     *
     * @return array|void
     */
    public function SplFileInfo(\SplFileInfo $file)
    {
        list($database, $connection_name, $extension, $timeExt) = $this->fileNameDatabaseConnectionNameExt($file);
        $exts = $this->manager->writes;
        if ( ! isset($exts[$extension])) {
            return;
        }
        $info["name"] = $file->getFilename();
        $info["database"] = $database;
        $info["connection_name"] = $connection_name;
        $info["filename"] = $file->getPathname();
        $info["ext"] = $extension;
        $info["class"] = $exts[$extension];
        $info["size"] = format_bytes($file->getSize());

        return $info;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return bool
     */
    public function copyright(WriteAbstract $write)
    {
        $config = $this->manager->getDatabaseConfig();
        $hostname = Arr::get($config, "hostname");
        $hostport = Arr::get($config, "hostport");
        $sql = "-- -----------------------------" . PHP_EOL;
        $sql .= "-- tp5-databackup SQL Dump " . PHP_EOL;
        $sql .= "-- version " . $this->manager->getVersion() . PHP_EOL;
        $sql .= "-- https://github.com/pkg6/tp5-databackup " . PHP_EOL;
        $sql .= "-- " . PHP_EOL;
        $sql .= "-- Host     : " . $hostname . PHP_EOL;
        $sql .= "-- Port     : " . $hostport . PHP_EOL;
        $sql .= "-- Database : " . $this->manager->getDatabase() . PHP_EOL;
        $sql .= "-- PHP Version : " . phpversion() . PHP_EOL;
        $sql .= "-- Date : " . date("Y-m-d H:i:s") . PHP_EOL;
        $sql .= "-- -----------------------------" . PHP_EOL . PHP_EOL;
        $sql .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL . PHP_EOL;
        $sql .= 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL;

        return $write->writeSQL($sql);
    }

    /**
     * 生成文件名.
     *
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
     * 生成完整路径.
     *
     * @param $filename
     *
     * @return string
     */
    public function generateFullPathFile($filename)
    {
        return $this->getPath() . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * 将文件名进行切割得到Database ConnectionName,日期+文件后缀,文件后缀
     * list($database, $connection_name,$extension,$timeExt) = $this->filename->fileNameDatabaseConnectionNameExt($file);.
     *
     * @param $fileName
     *
     * @return array
     */
    public function fileNameDatabaseConnectionNameExt($fileName)
    {
        $path_info = pathinfo($fileName);
        $ret = explode("-", $path_info["basename"]);

        return [$ret[0] ?? "", $ret[1] ?? "", $path_info["extension"] ?? "", $ret[2] ?? ""];
    }
}