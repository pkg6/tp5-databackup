<?php

namespace tp5er\Backup\sql;

use tp5er\Backup\SQLInterface;

class Mysql implements SQLInterface
{
    public function tables()
    {
        return "SHOW TABLE STATUS";
    }

    public function optimize($table = null)
    {
        if (is_array($table)) {
            $table = implode('`,`', $table);
        }
        return "OPTIMIZE TABLE `{$table}`";
    }

    public function repair($table = null)
    {
        if (is_array($table)) {
            $table = implode('`,`', $table);
        }
        return "REPAIR TABLE `{$table}`";
    }
}