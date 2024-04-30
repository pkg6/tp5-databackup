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
use tp5er\Backup\BackupInterface;

interface WriterInterface
{
    /**
     * @param App $app
     *
     * @return mixed
     */
    public function setApp(App $app);

    /**
     * @param array $config
     *
     * @return mixed
     */
    public function setConfig($config);

    /**
     * @param BackupInterface $backup
     *
     * @return mixed
     */
    public function setBackup(BackupInterface $backup);

    /**
     * @return string
     */
    public function type();

    /**
     * @return string
     */
    public function generateFileName();

    /**
     * @return array
     */
    public function files();
    /**
     * 写入sql.
     *
     * @param string $sql
     *
     * @return mixed
     */
    public function writeSQL(string $sql);

    /**
     * 写入sql.
     *
     * @param $file
     *
     * @return mixed
     */
    public function readSQL($file);
}
