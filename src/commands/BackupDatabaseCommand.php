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

class BackupDatabaseCommand extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('backup:database')
            ->addArgument('connection', Argument::OPTIONAL, 'Connect to database alias')
            ->setDescription('Back up all table structures and data in the database');
    }

    protected function execute(Input $input, Output $output)
    {
        $connection = $input->getArgument('connection');
        $backup = Backup::database($connection);
        //获取所有的表
        $table = $backup->tables();
        $tables = array_column($table, 'Name');
        try {
            $tableRun = $backup->backup($tables);
            foreach ($tableRun as $table => $ret) {
                if ($ret) {
                    $output->info("表结构与表数据备份完成 " . $table);
                } else {
                    $output->error("数据表备份失败 " . $table);
                }
            }
        } catch (\Exception $exception) {
            $output->error($exception->getMessage());
        }
    }

}
