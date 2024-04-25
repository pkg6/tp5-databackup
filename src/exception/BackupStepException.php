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

class BackupStepException extends \Exception
{
    public function __construct($step, $message)
    {
        parent::__construct(sprintf("An error occurred in step %s, error reason: %s", $step, $message), 0, null);
    }
}
