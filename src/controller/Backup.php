<?php

namespace tp5er\Backup\controller;

use think\facade\Db;
use tp5er\Backup\sql\Mysql;

class Backup
{
    //http://127.0.0.1:8000/index/tables
    public function tables()
    {

      $list=  \tp5er\Backup\facade\Backup::tables();

//        $sql = new Mysql();
//
//        $list=Db::query($sql->tables());

        var_dump($list);
    }
}