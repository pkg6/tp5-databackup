<?php

namespace tp5er\Backup\exception;

class BackupStepException extends \Exception
{
    public function __construct($step,$message)
    {
        parent::__construct(sprintf("An error occurred in step %s, error reason: %s", $step, $message), 0, null);
    }
}
