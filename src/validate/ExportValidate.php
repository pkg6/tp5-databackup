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

namespace tp5er\Backup\validate;

use think\Validate;

class ExportValidate extends Validate
{
    protected $rule = [
        'tables' => 'require',
        'index' => 'require',
        'page' => 'require',
    ];

    protected $message = [
        'tables.require' => '表数据不能为空',
        'index.require' => '表结构索引不能为空',
        'page.require' => '分页不能为空',
    ];

    protected $scene = [
        "step1" => ["tables"],
        "step2" => ["index", "page"]
    ];
}
