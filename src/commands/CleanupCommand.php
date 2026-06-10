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
use tp5er\Backup\facade\Backup;

class CleanupCommand extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('backup:cleanup')
            ->setDescription('Clear the cache that appears in the backup');
    }

    protected function execute(Input $input, Output $output)
    {
        Backup::cleanup();
        $output->info("清理成功 ");
    }
}
