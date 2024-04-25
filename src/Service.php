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

use tp5er\Backup\commands\Commands;

class Service extends \think\Service
{

    public function register()
    {

        define("backup_src_path", __DIR__ . DIRECTORY_SEPARATOR);

        $this->commands(Commands::commands());

        $this->app->bind(BackupInterface::class, function () {
            return new BackupManager($this->app);
        });
    }
}
