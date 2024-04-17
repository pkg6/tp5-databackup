[![Latest Stable Version](http://poser.pugx.org/tp5er/tp5-databackup/v)](https://packagist.org/packages/tp5er/tp5-databackup) [![Total Downloads](http://poser.pugx.org/tp5er/tp5-databackup/downloads)](https://packagist.org/packages/tp5er/tp5-databackup) [![Latest Unstable Version](http://poser.pugx.org/tp5er/tp5-databackup/v/unstable)](https://packagist.org/packages/tp5er/tp5-databackup) [![License](http://poser.pugx.org/tp5er/tp5-databackup/license)](https://packagist.org/packages/tp5er/tp5-databackup) [![PHP Version Require](http://poser.pugx.org/tp5er/tp5-databackup/require/php)](https://packagist.org/packages/tp5er/tp5-databackup)

## 最佳数据备份还原- shell脚本方式
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


## 使用本类进行数据库备份


### 使用composer进行安装
~~~
composer require tp5er/tp5-databackup
~~~

> 支持thinkphp6

### 作者已定义好controlle，只需定义一个controller进行继承

~~~
<?php
namespace app\controller;

use tp5er\Backup\controller\ApiController;

class Index extends ApiController
{

}

~~~

#### 接口说明

- /index/tables 获取所有的数据表

- /index/filelist 获取已经备份好的的文件
- /index/import 导入
- /index/export 导出
- /index/repair 修复表
- /index/optimize 优化表

> 导出的流程：
>
> 1. /index/export发送post请求，数据格式`{ "tables": ["admin","log"]}` 响应`['index' => 0, 'page' => 1]`
>2. /index/export发送get请求/index/export?index=0&page=0,直到page=0表示该数据备份完成
>
> 导入流程
> 
> 1. /index/filelist 拿到name传到/index/import?file=fastadmin-mysql-20240416184903.sql进行还原



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
