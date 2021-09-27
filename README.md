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

~~~


## 使用本类进行数据库备份

> demo 下载地址
> https://github.com/tp5er/tp5-databackup/tree/master/test


### 使用composer进行安装
~~~
优先
composer require tp5er/tp5-databackup
~~~

> 同时支持tp6和tp5版本

### 引入类文件
~~~
use \tp5er\Backup;
~~~

### 参数说明
~~~
$start：无论是备份还是还原只要一张表备份完成$start就是返回的0
$file ：sql文件的名字，下面有名字命名规范，如果名字命令不规范，在展示列表中就会出现错误
~~~

### 配置文件
~~~
$config=array(
    'path'     => './Data/',//数据库备份路径
    'part'     => 20971520,//数据库备份卷大小
    'compress' => 0,//数据库备份文件是否启用压缩 0不压缩 1 压缩
    'level'    => 9 //数据库备份文件压缩级别 1普通 4 一般  9最高
);
~~~

### 实例化
~~~
 $db= new Backup($config);
~~~

### 文件命名规则，请严格遵守（温馨提示）
~~~
$file=['name'=>date('Ymd-His'),'part'=>1]
~~~

### 数据类表列表
~~~
return $this->fetch('index',['list'=>$db->dataList()]);
~~~
### 备份文件列表
~~~
  return $this->fetch('importlist',['list'=>$db->fileList()]);
~~~


### 备份表
~~~
 $tables="数据库表1";
 $start= $db->setFile($file)->backup($tables[$id], 0);

~~~

### 导入表
~~~
 $start=0;
 $start= $db->setFile($file)->import($start);
~~~

### 删除备份文件
~~~
    $db->delFile($time);
~~~

### 下载备份文件
~~~
    $db->downloadFile($time);
~~~

### 修复表
~~~
    $db->repair($tables)
~~~

### 优化表
~~~
    $db->optimize($tables)
~~~



### 大数据备份采取措施1
~~~
如果备份数据比较大的情况下，需要修改如下参数
//默认php代码能够申请到的最大内存字节数就是134217728 bytes，如果代码执行的时候再需要更多的内存,根据情况定义指定字节数
memory_limit = 1024M
//默认php代码申请到的超时时间是20s，如果代码执行需要更长的时间，根据代码执行的超时时间定义版本运行超时时间
max_execution_time =1000
~~~

### 大数据备份采取措施2

~~~
    自由设置超时时间。支持连贯操作，该方法主要使用在表备份和还原中，防止备份还原和备份不完整
    //备份
    $time=0//表示不限制超时时间，直到程序结束，(慎用)
    $db->setTimeout($time)->setFile($file)->backup($tables[$id], 0);
    //还原
    $db->setTimeout($time)->setFile($file)->import($start);
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

# QQ交流

[1751212020](http://wpa.qq.com/msgrd?v=3&uin=1751212020&site=qq&menu=yes)


