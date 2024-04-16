<?php

namespace tp5er\Backup\exception;


class ClassDefineException extends \Exception
{
    public function __construct($sqlClass, $interface)
    {
        parent::__construct(sprintf("%s not implemented OR extends  %s", $sqlClass, $interface), 0, null);
    }
}