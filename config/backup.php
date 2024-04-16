<?php

return [
    "default" => "sql",
    "path"    => app()->getRootPath() . "backup",
    "backups" => [
        "sql" => [
            "type" => 'sql',
        ]
    ],
    "build"   => \tp5er\Backup\build\Mysql::class,
];