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

return [
    "default" => "sql",
    "path" => app()->getRootPath() . "backup",
    "backups" => [
        "sql" => [
            "type" => 'sql',
        ]
    ],
    //一次请求存储100条数据
    "limit" => 100,
    //生成sql语句的class（目前支持mysql,如需其他可以参考自行修改）
    "build" => \tp5er\Backup\build\Mysql::class,
];
