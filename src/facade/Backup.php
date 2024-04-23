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
use tp5er\Backup\BackupInterface;
use tp5er\Backup\build\BuildSQLInterface;
use tp5er\Backup\FileInfo;
use tp5er\Backup\provider\ProviderInterface;
use tp5er\Backup\write\WriteAbstract;

/**
 * Class Backup.
 *
 * @method static BackupInterface setBuildSQL(BuildSQLInterface $buildSQL = null)
 * @method static BackupInterface setWrite(WriteAbstract $write)
 * @method static ProviderInterface getProviderObject($connection = null, $writeType = null)
 * @method static BackupInterface database($database = null)
 * @method static array tables()
 * @method static mixed optimize($tables = null)
 * @method static mixed repair($tables = null)
 * @method static array backup(array $tables)
 * @method static bool backupStep1(array $tables)
 * @method static bool backupStep2($index = 0, $page = 0)
 * @method static void cleanup()
 * @method static FileInfo[] files()
 * @method static bool import($fileName)
 */
class Backup extends Facade
{
    protected static function getFacadeClass()
    {
        return BackupInterface::class;
    }
}
