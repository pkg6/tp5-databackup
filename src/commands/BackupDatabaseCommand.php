<?php

namespace tp5er\Backup\commands;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use tp5er\Backup\facade\Backup;

class BackupDatabaseCommand extends Command
{
    protected function configure()
    {
        $this->setName('backup:database')
            ->addArgument('connection', Argument::OPTIONAL, 'Connect to database alias')
            ->setDescription('Back up all table structures and data in the database');
    }

    protected function execute(Input $input, Output $output)
    {
        $connection = $input->getArgument('connection');
        $backup = Backup::database($connection);
        $tables = array_column($backup->tables(), 'Name');

        try {
            $backup->backup($tables);
            $output->info('所有数据表备份完成');
        } catch (\Exception $e) {
            $output->error($e->getMessage());
        }
    }
}
