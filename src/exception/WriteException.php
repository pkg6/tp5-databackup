<?php

namespace tp5er\Backup\exception;

class WriteException extends \Exception
{
    public $filename;

    public function __construct($fileName)
    {
        $this->filename = $fileName;
        //检测到有一个备份任务正在执行，请稍后再试！
        parent::__construct(sprintf("Write failed %s", $this->filename), 0, null);
    }
}