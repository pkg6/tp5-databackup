<?php

namespace tp5er\Backup\facade;

use think\Facade;
use tp5er\Backup\BackupManager;
use tp5er\Backup\BuildSQLInterface;
use tp5er\Backup\WriteAbstract;

/**
 * Class Backup
 * @method static BackupManager setBuildSQL(BuildSQLInterface $buildSQL = null)
 * @method static BackupManager setWrite(WriteAbstract $write)
 * @method static BackupManager database($database = null)
 * @method static array tables()
 * @method static mixed optimize($tables = null)
 * @method static mixed repair($tables = null)
 *
 * @method static bool apiBackupStep1(array $tables)
 * @method static bool apiBackupStep2($index = 0, $offset = 0)
 *
 * @method static bool import($fileName)
 *
 *
 *
 *
 *
 *
 */
class Backup extends Facade
{
    protected static function getFacadeClass()
    {
        return 'tp5er.backup';
    }
}