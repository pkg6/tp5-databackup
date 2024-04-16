<?php

namespace tp5er\Backup\exception;


class FileException extends \RuntimeException
{
    public function __construct($file)
    {
        parent::__construct(sprintf("Unable to find %s file",$file));
    }

}