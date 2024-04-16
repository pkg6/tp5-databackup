<?php

namespace tp5er\Backup\facade;

use think\Facade;

class Backup extends Facade
{
    protected static function getFacadeClass()
    {
        return 'tp5er.backup';
    }
}