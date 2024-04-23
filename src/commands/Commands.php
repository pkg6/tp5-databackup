<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\commands;

final class Commands
{
    public static function commands()
    {
        return [
            BackupDatabaseCommand::class,
            ImportDatabaseCommand::class,
            ListCommand::class,
            CleanupCommand::class,
            BackupCommand::class,
        ];
    }
}
