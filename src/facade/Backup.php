<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\facade;

use think\Facade;
use tp5er\Backup\BackupManager;
use tp5er\Backup\BuildSQLInterface;
use tp5er\Backup\FileInfo;
use tp5er\Backup\ProviderInterface;
use tp5er\Backup\WriteAbstract;

/**
 * Class Backup.
 *
 * @method static BackupManager setBuildSQL(BuildSQLInterface $buildSQL = null)
 * @method static BackupManager setWrite(WriteAbstract $write)
 * @method static BackupManager database($database = null)
 * @method static ProviderInterface provider($connection = null, $writeType = null)
 * @method static array tables()
 * @method static mixed optimize($tables = null)
 * @method static mixed repair($tables = null)
 * @method static array backup(array $tables)
 * @method static bool backupStep1(array $tables)
 * @method static bool backupStep2($index = 0, $page = 0)
 * @method static void cleanup()
 * @method static bool import($fileName)
 * @method static FileInfo[] files()
 */
class Backup extends Facade
{
    protected static function getFacadeClass()
    {
        return 'tp5er.backup';
    }
}
