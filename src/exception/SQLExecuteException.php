<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\exception;

class SQLExecuteException extends \Exception
{

    public $index;
    public $sql;
    public $exception;

    public function __construct($index, $sql, \Exception $exception)
    {
        $this->index = $index;
        $this->exception = $exception;
        $this->sql = $sql;
        parent::__construct(sprintf("Execute SQL `%s` index:`%d`, reason: %s", $sql, $index, $exception->getMessage()));
    }
}
