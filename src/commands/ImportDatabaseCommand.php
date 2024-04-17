<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\commands;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use tp5er\Backup\facade\Backup;

class ImportDatabaseCommand extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('backup:import')
            ->addArgument('filename', Argument::REQUIRED, 'Enter file name for example: fastadmin-mysql-20240417201417.sql')
            ->addArgument('connection', Argument::OPTIONAL, 'Connect to database alias')
            ->setDescription('Restore backup files to the database');
    }

    protected function execute(Input $input, Output $output)
    {
        $connection = $input->getArgument('connection');
        $filename = $input->getArgument('filename');
        $backup = Backup::database($connection);
        try {
            $backup->import($filename);
        } catch (\Exception $exception) {
            $output->error($exception->getMessage());
        }
    }
}
