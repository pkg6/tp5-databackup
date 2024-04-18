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
use think\console\Table;
use tp5er\Backup\facade\Backup;
use tp5er\Backup\FileInfo;

class ListCommand extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('backup:list')
            ->setDescription('List local backups to files');
    }

    protected function execute(Input $input, Output $output)
    {
        $table = new Table();
        $table->setHeader(FileInfo::Properties());
        foreach (Backup::files() as $item) {
            $row[] = $item->toArray();
        }
        $table->setRows($row);

        return $this->table($table);
    }
}
