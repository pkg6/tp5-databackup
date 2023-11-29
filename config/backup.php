<?php

return [
    'path' => \app()->getRootPath() . "backup/",
    //数据库备份路径
    'part' => 20971520,
    //数据库备份卷大小
    'compress' => 0,
    //数据库备份文件是否启用压缩 0不压缩 1 压缩
    'level' => 9,
];