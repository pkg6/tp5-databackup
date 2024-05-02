[![Latest Stable Version](http://poser.pugx.org/tp5er/tp5-databackup/v)](https://packagist.org/packages/tp5er/tp5-databackup) [![Total Downloads](http://poser.pugx.org/tp5er/tp5-databackup/downloads)](https://packagist.org/packages/tp5er/tp5-databackup) [![Latest Unstable Version](http://poser.pugx.org/tp5er/tp5-databackup/v/unstable)](https://packagist.org/packages/tp5er/tp5-databackup) [![License](http://poser.pugx.org/tp5er/tp5-databackup/license)](https://packagist.org/packages/tp5er/tp5-databackup) [![PHP Version Require](http://poser.pugx.org/tp5er/tp5-databackup/require/php)](https://packagist.org/packages/tp5er/tp5-databackup)



## 重要的事情说三遍！！！重要的事情说三遍！！！重要的事情说三遍！！！

> 1. [pkg6](https://github.com/pkg6)都是作者自己一个人在维护，欢迎提交[pull request](https://github.com/pkg6/think-backup/pulls) 减少本人的精力
> 2. 作者使用的php版本是php7.4，目测写的方法兼容8以上，如果不兼容，可以提交[pull request](https://github.com/pkg6/think-backup/pulls)，记得写一下注释哦！！！
> 3. 通过队列或命令行的方式，再也不用担心数据备份不完整

## 使用本类进行数据库备份


### 使用composer进行安装
~~~
composer require tp5er/tp5-databackup
~~~

### 使用方式1: 继承 `tp5er\Backup\controller\BackupController`

> 重要的事情说三遍！！！重要的事情说三遍！！！重要的事情说三遍！！！
>
> 在thinkphp框架中定义一个控制器，然后继承`tp5er\Backup\controller\BackupController`，然后跳转到`BackupController`控制器中查看方法，都是中国人看的懂中国话。

### 使用方式2: 使用路由`route/app.php`

> 通过路由使用案例： \tp5er\Backup\Route::route();
>
> 由于页面使用layui渲染的前端页面，你可以参考前端页面自己量身定做，然后使用\tp5er\Backup\Route::api();调用接口也是可以的哦

~~~
<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
use think\facade\Route;

Route::get('think', function () {
    return 'hello,ThinkPHP6!';
});

Route::get('hello/:name', 'index/hello');

\tp5er\Backup\Route::route();
~~~

### 使用方式3: 通过队列的方法

#### 还原数据

~~~
$data["database"]="mysql";
$data["opt"]="import";
$data["filename"]="fastadmin-mysql-20240416184903.sql";
backup_queue($data);
~~~

#### 备份数据/修复表/优化表

~~~
$data["database"]="mysql";
$data["opt"]="backup" //backup,repair,optimize;
$data["table"]=["fa_category","fa_auth_rule"];
backup_queue($data);
~~~

### 使用方式4: 通过命令行

~~~
//进入交互模式进行相关操作
php think backup:choice

//备份整个数据库表结构和表数据
php think backup:database

//还原数据
php think backup:import fastadmin-mysql-20240416184903.sql

//列出所有备份文件
php think backup:list

//在执行本代码中会使用到缓存这里是清理缓存的命令
php think backup:cleanup
~~~

## 事件`参考代码`

~~~
<?php

namespace tp5er\Backup;

use think\facade\Event as tpEvent;


class Event
{
    //在备份第一步使用事件
    const backupStep1 = "tp5er.backup.step1";
    //在备份第二步使用初始事件
    const backupStep2 = "tp5er.backup.step2";
    //在备份第二步备份数据使用的事件
    const backupStep2Data = "tp5er.backup.step2.data";
    
    /**
     * @return void
     */
    public static function event()
    {
        tpEvent::listen(Event::backupStep1, function ($data) {
            /**
             * @var BackupInterface $backup
             */
            list($backup, $filename, $table, $ret) = $data;

            //在备份第一步执行
            //Your TODO
        });
        tpEvent::listen(Event::backupStep2, function ($data) {
            /**
             * @var BackupInterface $backup
             */
            list($backup, $filename, $table) = $data;
            //Your TODO

        });
        tpEvent::listen(Event::backupStep2Data, function ($data) {
            /**
             * @var BackupInterface $backup
             */
            list($backup, $filename, $sql, $lastPageOrIsBackupData) = $data;
            //Your TODO
        });
    }

}
~~~

## 基础概念

reader：定义读取SQL的方法，目前只支持Mysql，自定义扩展可以实现  `tp5er\Backup\reader\ReaderInterface`

writer：定义的写入SQL方法，目前只支持File，自定义扩展可以实现  `tp5er\Backup\writer\WriterInterface`

Factory：定义reader和writer对象初始化

BackupManager：所有的相关操作都在这里

## 版本修改记录

[CHANGELOG.md](https://github.com/pkg6/think-backup/blob/main/CHANGELOG.md)

## 提交代码规范

1. fork一份代码到自己账号下,生成如 `test/tp5-databackup`
2. 拉去一个分支 `develop` ，看个人习惯，无所谓
3. 将develop提交[pull request](https://github.com/pkg6/think-backup/pulls) 
4. 等待作者合并
5. 定期（当接受到第一个合并请求开始计算一周之内）会打tag，tag规范：2.1.x 作为新方法新类的出现，2.x.x作为对以往的方法和类进行变更甚至是破坏性的变更

## 分支说明

main 分支 ：2.x版本最新代码，tag2.x标签是在该分支进行上

master分支 ：1.x版本的最新代码，tag1.x标签是在该分支进行上

develop分支：作者的开发分支，主要开发2.x版本的bug

## 作者案例开发流程

~~~
composer create-project topthink/think tp
cd tp
composer require tp5er/tp5-databackup dev-develop
rm -rf vendor/tp5er/tp5-databackup
git clone -b develop git@github.com:pkg6/tp5-databackup.git vendor/tp5er/tp5-databackup
~~~

> 进入vendor/tp5er/tp5-databackup进行修改代码，然后进行提交代码，提交[pull request](https://github.com/pkg6/think-backup/pulls) 进行合并到main分支

## 其他手段进行数据备份还原

### mysql常见命令

~~~

//备份整个数据库
mysqldump -uroot -hhost -ppassword dbname > backdb.sql
//备份数据库中的某个表
mysqldump -uroot -hhost -ppassword dbname tbname1, tbname2 > backdb.sql
//备份多个数据库
mysqldump -uroot -hhost -ppassword --databases dbname1, dbname2 > backdb.sql
//备份系统中所有数据库
mysqldump -uroot -hhost -ppassword --all-databases > backdb.sql


//恢复
mysql -uroot -p'123456' dbname < backdb.sql 


//远程备份与还原
备份数据库 192.168.3.10 root 123456 test
mysqldump -h 192.168.3.10 -u root -p123456 test > test.sql
还原数据库 192.168.3.11 root 123456 test
mysql -h 192.168.3.11 -P 3306 -u root -p123456 test < test.sql
~~~

### 备份shell脚本

~~~
#!/bin/bash

# 1.备份全部数据库的数据和结构
# mysqldump -uroot -p123456 -A > /data/mysqlDump/mydb.sql
# 2.备份全部数据库的结构（加 -d 参数）
# mysqldump -uroot -p123456 -A -d > /data/mysqlDump/mydb.sql
# 3.备份全部数据库的数据(加 -t 参数)
# mysqldump -uroot -p123456 -A -t > /data/mysqlDump/mydb.sql
# 4.备份单个数据库的数据和结构(数据库名mydb)
# mysqldump -uroot-p123456 mydb > /data/mysqlDump/mydb.sql
# 5.备份单个数据库的结构
# mysqldump -uroot -p123456 mydb -d > /data/mysqlDump/mydb.sql
# 6.备份单个数据库的数据
# mysqldump -uroot -p123456 mydb -t > /data/mysqlDump/mydb.sql
# 7.备份多个表的数据和结构（数据，结构的单独备份方法与上同）
# mysqldump -uroot -p123456 mydb t1 t2 > /data/mysqlDump/mydb.sql
# 8.一次备份多个数据库
# mysqldump -uroot -p123456 --databases db1 db2 > /data/mysqlDump/mydb.sql

# 1.在系统命令行中，输入如下实现还原：
# mysql -uroot -p123456 < /data/mysqlDump/mydb.sql
# 2.在登录进入mysql系统中,通过source指令找到对应系统中的文件进行还原：
# mysql> source /data/mysqlDump/mydb.sql


#保存备份个数，备份31天数据
number=31
#备份保存路径
backup_dir=/root/mysqlbackup
#日期
dd=`date +%Y-%m-%d-%H-%M-%S`
#备份工具
tool=mysqldump
#用户名
username=root
#密码
password=123456
#将要备份的数据库
database_name=demo

#如果文件夹不存在则创建
if [ ! -d $backup_dir ];
then     
    mkdir -p $backup_dir;
fi

#简单写法 mysqldump -u root -p123456 users > /root/mysqlbackup/users-$filename.sql
$tool -u $username -p$password $database_name > $backup_dir/$database_name-$dd.sql

#写创建备份日志
echo "create $backup_dir/$database_name-$dd.dupm" >> $backup_dir/log.txt

#找出需要删除的备份
delfile=`ls -l -crt $backup_dir/*.sql | awk '{print $9 }' | head -1`

#判断现在的备份数量是否大于$number
count=`ls -l -crt $backup_dir/*.sql | awk '{print $9 }' | wc -l`

if [ $count -gt $number ]
then
  #删除最早生成的备份，只保留number数量的备份
  rm $delfile
  #写删除文件日志
  echo "delete $delfile" >> $backup_dir/log.txt
fi
~~~
