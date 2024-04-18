<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\validate;

use think\Validate;
use tp5er\Backup\OPT;

/**
 * @package tp5er\Backup\validate
 */
class BackupValidate extends Validate
{
    protected $rule = [
        'opt' => "require|in:" . OPT::import . "," . OPT::backup,
        "database" => "require",
        "filename" => "require",
        "tables" => "require",
    ];

    protected $message = [
        'opt.require' => '操作不能为空',
        'database.require' => '数据库连接不能为空',
        'filename.require' => '文件名不能为空',
        'tables.require' => '表数据不能为空',
    ];

    protected $scene = [
        //导入的验证数据
        OPT::import => ["opt", "database", "filename"],
        //导出的验证数据
        OPT::backup => ["opt", "database", "tables"],
    ];

}
