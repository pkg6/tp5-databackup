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
    "default" => "file",
    "backups" => [
        "file" => [
            //目前只支持sql文件
            "write_type" => 'file',
            //读取生成sql语句的类
            "reader_type" => 'mysql',
            //sql文件存储路径
            "path" => app()->getRootPath() . "backup",
        ]
    ],
    //一次请求存储100条数据
    "limit" => 100,
];
