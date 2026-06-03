<?php

namespace tp5er\Backup\commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use tp5er\Backup\facade\Backup;

class BackupCommand extends Command
{
    protected function configure()
    {
        $this->setName('backup:choice')
            ->setDescription('Backup and restore data through interactive means');
    }

    protected function execute(Input $input, Output $output)
    {
        $databaseConnections = $this->app->config->get('database.connections');
        if (empty($databaseConnections)) {
            $output->error('没有可用的数据库连接');

            return;
        }

        $database = $output->choice($input, '选择需要操作的数据库连接', array_keys($databaseConnections));
        $backup = Backup::database($database);

        $opt = $output->choice($input, '选择操作方式', ['import', 'backup', 'repair', 'optimize']);

        if ($opt === 'import') {
            $this->caseImport($backup, $input, $output);
        } else {
            $this->caseOther($backup, $opt, $input, $output);
        }
    }

    protected function caseOther($backup, string $opt, Input $input, Output $output)
    {
        $dbTable = $backup->tables();
        $tables = array_column($dbTable, 'Name');
        $backupTable = [];

        if (empty($tables)) {
            $output->error('没有数据表可供选择');

            return;
        }

        foreach ($tables as $table) {
            $yes = $output->confirm($input, sprintf('是否选择 `%s` 表进行 %s，默认是 ?', $table, $opt));
            if ($yes) {
                $backupTable[] = $table;
            }
        }

        if (empty($backupTable)) {
            $output->error('没有可供的表');

            return;
        }

        try {
            switch ($opt) {
                case 'repair':
                    $backup->repair($backupTable);
                    $output->info('修复表数据处理完成');
                    break;
                case 'optimize':
                    $backup->optimize($backupTable);
                    $output->info('优化表数据处理完成');
                    break;
                case 'backup':
                    $backup->backup($backupTable);
                    $output->info('备份数据处理完成');
                    break;
                default:
                    $output->error('无法处理你的操作 ' . $opt);
            }
        } catch (\Exception $e) {
            $output->error('处理失败 err=' . $e->getMessage());
        }
    }

    protected function caseImport($backup, Input $input, Output $output)
    {
        $files = array_column($backup->files(), 'name');
        if (empty($files)) {
            $output->error('没有可以选择的备份文件');

            return;
        }

        $file = $output->choice($input, 'Select file', $files);
        try {
            $backup->import($file);
            $output->info('数据还原成功 ' . $file);
        } catch (\Exception $e) {
            $output->error(sprintf('数据还原失败 %s err=%s', $file, $e->getMessage()));
        }
    }
}
