<?php

namespace tp5er\Backup\facade;

use think\Facade;
use tp5er\Backup\DbBackup as BackupService;

/**
 * @method static BackupService database($database = null)
 * @method static array tables()
 * @method static mixed optimize($tables = null)
 * @method static mixed repair($tables)
 * @method static bool backup(array $tables)
 * @method static array export(array $tables)
 * @method static int exportStep(string $file, string $table, int $page)
 * @method static array files()
 * @method static bool import($fileName)
 */
class Backup extends Facade
{
    protected static function getFacadeClass()
    {
        return BackupService::class;
    }
}
