<?php

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
            $output->info('数据还原成功 ' . $filename);
        } catch (\Exception $e) {
            $output->error($e->getMessage());
        }
    }
}
