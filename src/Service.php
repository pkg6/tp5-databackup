<?php

namespace tp5er\Backup;

use tp5er\Backup\commands\Commands;

class Service extends \think\Service
{
    public function register()
    {
        $this->commands(Commands::commands());

        $this->app->bind(DbBackup::class, function () {
            return new DbBackup($this->app);
        });
    }
}
