<?php

namespace tp5er\Backup;

class Service extends \think\Service
{
    public function register()
    {
        $this->app->bind('tp5er.backup', function () {
            return new BackupManager($this->app);
        });
    }
}