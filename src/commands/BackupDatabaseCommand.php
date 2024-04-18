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
use tp5er\Backup\BackupManager;
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
            //创建任务
            if ($backup->backupStep1($tables)) {
                foreach ($tables as $index => $table) {
                    //任务创建成功
                    $lastPage = $this->backup2($backup, $index);
                    if ($lastPage === 0) {
                        $output->info("表结构与表数据备份完成 " . $table);
                    } else {
                        $output->error("数据表备份失败 " . $table);
                        $backup->cleanup();
                    }
                }
            }
            $backup->cleanup();
        } catch (\Exception $exception) {
            $output->error($exception->getMessage());
        }
    }

    /**
     * 备份单表结果与数据.
     *
     * @param BackupManager $manager
     * @param $index
     * @param $page
     *
     * @return int
     *
     * @throws \tp5er\Backup\exception\BackupStepException
     * @throws \tp5er\Backup\exception\ClassDefineException
     * @throws \tp5er\Backup\exception\WriteException
     */
    protected function backup2(BackupManager $manager, $index = 0, $page = 1)
    {
        //任务创建成功
        $lastPage = $manager->backupStep2($index, $page);
        if ($lastPage > 0) {
            return $this->backup2($manager, $index, $lastPage);
        }

        return $lastPage;
    }
}
