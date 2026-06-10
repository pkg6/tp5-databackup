<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * (L) Licensed <https://opensource.org/license/MIT>
 *
 * (A) zhiqiang <https://www.zhiqiang.wang>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\Table;
use tp5er\Backup\facade\Backup;

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
        $header = [
            'name',
            "database",
            "connection",
            "write_type",
            "reader_type",
            "version",
            "size"
        ];
        $files = Backup::files();
        $table->setHeader($header);
        $row = [];
        foreach ($files as $i => $fileInfo) {
            foreach ($header as $f) {
                if (isset($fileInfo[$f])) {
                    $row[$i][$f] = $fileInfo[$f];
                }
            }
        }
        $table->setRows($row);

        return $this->table($table);
    }
}
