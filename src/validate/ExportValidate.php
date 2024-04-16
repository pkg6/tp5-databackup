<?php

namespace tp5er\Backup\validate;

use think\Validate;

class ExportValidate extends Validate
{
    protected $rule = [
        'tables' => 'require',
        'index'  => 'require',
        'offset' => 'require',
    ];

    protected $message = [
        'tables.require' => '表数据不能为空',
        'index.require'  => '表结构索引不能为空',
        'offset.require' => '游标不能为空',
    ];

    protected $scene = [
        "step1" => ["tables"],
        "step2" => ["index", "offset"]
    ];
}