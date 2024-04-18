<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\write;

use think\App;
use tp5er\Backup\BackupManager;

abstract class WriteAbstract
{
    /**
     * @var App
     */
    protected $app;

    /**
     * @var BackupManager
     */
    protected $manager;

    /**
     * 写入的文件名.
     *
     * @var string
     */
    protected $filename;

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
     * @param BackupManager $manager
     *
     * @return void
     */
    public function setManager(BackupManager $manager)
    {
        $this->manager = $manager;

    }

    /**
     * @param $filename
     *
     * @return void
     */
    public function setFileName($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->filename . "." . $this->ext();
    }

    /**
     * @param string $sql
     *
     * @return bool
     */
    abstract public function writeSQL(string $sql);

    abstract public function readSQL($file);

    abstract public function ext();

    /**
     *
     */
    public function __destruct()
    {
    }
}
