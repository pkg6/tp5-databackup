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

use think\facade\Event as tpEvent;

class Event
{
    //在备份第一步使用事件
    const backupStep1 = "tp5er.backup.step1";
    //在备份第二步使用初始事件
    const backupStep2 = "tp5er.backup.step2";
    //在备份第二步备份数据使用的事件
    const backupStep2Data = "tp5er.backup.step2.data";

    /**
     * @return void
     */
    public static function event()
    {
        tpEvent::listen(Event::backupStep1, function ($data) {
            /**
             * @var BackupInterface $backup
             */
            list($backup, $filename, $table, $ret) = $data;

            //在备份第一步执行
            //Your TODO
        });
        tpEvent::listen(Event::backupStep2, function ($data) {
            /**
             * @var BackupInterface $backup
             */
            list($backup, $filename, $table) = $data;
            //Your TODO

        });
        tpEvent::listen(Event::backupStep2Data, function ($data) {
            /**
             * @var BackupInterface $backup
             */
            list($backup, $filename, $sql, $lastPageOrIsBackupData) = $data;
            //Your TODO
        });
    }

}
