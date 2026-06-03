<?php

namespace tp5er\Backup\commands;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\Table as ConsoleTable;
use tp5er\Backup\facade\Backup;

class ListCommand extends Command
{
    protected function configure()
    {
        $this->setName('backup:list')
            ->setDescription('List local backups to files');
    }

    protected function execute(Input $input, Output $output)
    {
        $table = new ConsoleTable();
        $header = ['name', 'size', 'time'];
        $table->setHeader($header);
        $files = Backup::files();
        $rows = [];
        foreach ($files as $i => $info) {
            foreach ($header as $f) {
                $rows[$i][$f] = $info[$f] ?? '';
            }
        }
        $table->setRows($rows);

        return $this->table($table);
    }
}
