<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup;

use tp5er\Backup\commands\BackupDatabaseCommand;
use tp5er\Backup\commands\ImportDatabaseCommand;
use tp5er\Backup\commands\ListCommand;

class Service extends \think\Service
{

    public function register()
    {
        $this->commands([
            BackupDatabaseCommand::class,
            ImportDatabaseCommand::class,
            ListCommand::class,
        ]);

        $this->app->bind('tp5er.backup', function () {
            return new BackupManager($this->app);
        });
    }
}
