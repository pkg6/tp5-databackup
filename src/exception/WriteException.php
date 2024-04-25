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
