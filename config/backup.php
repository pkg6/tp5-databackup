<?php

return [
    "default" => "sql",
    "path"    => app()->getRootPath() . "backup",
    "backups" => [
        "sql" => [
            "type" => 'sql',
        ]
    ],
    //一次请求存储100条数据
    "limit"   => 100,
    //生成sql语句的class（目前支持mysql,如需其他可以参考自行修改）
    "build"   => \tp5er\Backup\build\Mysql::class,
];