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

class ClassDefineException extends \Exception
{
    public function __construct($sqlClass, $interface)
    {
        parent::__construct(sprintf("%s not implemented OR extends  %s", $sqlClass, $interface), 0, null);
    }
}
