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

    const SQLFileWriterExt = "sql";

    const backupPageTableDoesNotExist = -1;
    const backupPageTableOver = 0;

    /**
     * 支持的数据表操作.
     *
     * @return string[]
     */
    public static function opts()
    {
        return [OPT::import, OPT::backup, OPT::repair, OPT::optimize];
    }

    /*
     *  在备份中page的含义
     */
    public static function backupPage($page)
    {
        switch ($page) {
            case OPT::backupPageTableDoesNotExist:
                return "表不存在";
            case OPT::backupPageTableOver:
                return "表数据已备份完毕";
            default:
                return "继续备份表数据";
        }
    }

}
