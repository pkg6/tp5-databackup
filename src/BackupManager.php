<?php

namespace tp5er\Backup;

use think\App;


class BackupManager
{
    /**
     * @var App
     */
    protected $app;

    public function __construct(App $app)
    {
        $this->app      = $app;
    }
}