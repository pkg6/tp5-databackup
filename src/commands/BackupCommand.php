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
use think\console\Output;
use tp5er\Backup\BackupManager;
use tp5er\Backup\facade\Backup;
use tp5er\Backup\OPT;

class BackupCommand extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('backup:choice')
            ->setDescription('Backup and restore data through interactive means');
    }

    protected function execute(Input $input, Output $output)
    {
        $databaseConnections = $this->app->config->get("database.connections");
        if (count($databaseConnections) <= 0) {
            $output->error("没有可用的数据库连接");

            return;
        }
        //选择需要操作数据库
        $database = $output->choice($input, "选择需要操作的数据库连接", array_keys($databaseConnections));
        $backup = Backup::database($database);

        $opt = $output->choice($input, "选择操作方式", OPT::opts());

        if ($opt == OPT::import) {
            $this->caseImport($backup, $opt, $output);
        } else {
            $this->caseOther($backup, $opt, $input, $output);
        }
    }

    protected function caseOther(BackupManager $backup, $opt, Input $input, Output $output)
    {

        $db_table = $backup->tables();
        $tables = array_column($db_table, 'Name');
        $backupTable = [];

        if (count($tables) <= 0) {
            $output->error("没有数据表可以可提供选择");
        }
        foreach ($tables as $table) {
            $yes = $output->confirm($input, sprintf("是否选择 `%s` 表进行 %s，默认是 ?", $table, $opt));
            if ($yes) {
                $backupTable[] = $table;
            }
        }
        if (count($backupTable) <= 0) {
            $output->error("没有可供的表");

            return;
        }
        try {
            switch ($opt) {
                case OPT::repair:
                    $ret = $backup->repair($backupTable);
                    if ($ret) {
                        $output->info("修复表数据处理完成 ");
                    } else {
                        $output->error("修复表数据处理失败 " . $table);
                    }
                    break;
                case OPT::optimize:
                    $ret = $backup->backup($backupTable);
                    if ($ret) {
                        $output->info("优化表数据处理完成 ");
                    } else {
                        $output->error("优化表数据处理失败 " . $table);
                    }
                    break;
                case OPT::backup:
                    $tableRun = $backup->backup($backupTable);
                    foreach ($tableRun as $table => $ret) {
                        if ($ret) {
                            $output->info("备份数据处理完成 " . $table);
                        } else {
                            $output->error("备份数据处理失败 " . $table);
                        }
                    }
                    break;
                default:
                    $output->error("无法处理你的操作 " . $opt);
            }

        } catch (\Exception $exception) {
            $output->error("处理失败 err=" . $exception->getMessage());
        }
    }

    protected function caseImport(BackupManager $backup, Input $input, Output $output)
    {
        $files = array_column($backup->files(), "name");
        if (count($files) <= 0) {
            $output->error("没有可以选择的备份文件");

            return;
        }
        $file = $output->choice($input, "Select file", $files);
        try {

            $ret = $backup->import($file);
            if ($ret) {
                $output->info("数据还原成功 " . $file);
            }
        } catch (\Exception $exception) {
            $output->error(sprintf("数据还原失败 %s err=%s", $file, $exception->getMessage()));
        }
    }
}
