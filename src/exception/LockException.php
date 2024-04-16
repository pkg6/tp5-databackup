<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\exception;

class LockException extends \Exception
{
    public $lock;

    public function __construct($lock)
    {
        $this->lock = $lock;
        //检测到有一个备份任务正在执行，请稍后再试！
        parent::__construct(sprintf("The backup task is currently in progress. If it needs to be terminated, please delete %s", $this->lock), 0, null);
    }
}
