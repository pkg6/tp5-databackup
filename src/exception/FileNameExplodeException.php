<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\exception;

class FileNameExplodeException extends \Exception
{

    public $reason = "File format parsing failed. The possible reason is an old version or a custom file in the directory.";
    public function __construct($file)
    {
        parent::__construct(sprintf("`%s` File parsing failed", $file));
    }
}
