<?php

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