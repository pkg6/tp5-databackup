<?php

namespace tp5er\Backup\commands;

final class Commands
{
    public static function commands()
    {
        return [
            BackupDatabaseCommand::class,
            ImportDatabaseCommand::class,
            ListCommand::class,
            BackupCommand::class,
        ];
    }
}
