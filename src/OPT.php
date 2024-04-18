<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup;

final class OPT
{
    //还原
    const import = "import";
    //备份
    const backup = "backup";
    //修复表
    const repair = "repair";
    //优化表
    const optimize = "optimize";

    public static function opts()
    {
        return [OPT::import, OPT::backup,OPT::repair,OPT::optimize];
    }

}
