[![Latest Stable Version](http://poser.pugx.org/tp5er/tp5-databackup/v)](https://packagist.org/packages/tp5er/tp5-databackup)
[![Total Downloads](http://poser.pugx.org/tp5er/tp5-databackup/downloads)](https://packagist.org/packages/tp5er/tp5-databackup)
[![Latest Unstable Version](http://poser.pugx.org/tp5er/tp5-databackup/v/unstable)](https://packagist.org/packages/tp5er/tp5-databackup)
[![License](http://poser.pugx.org/tp5er/tp5-databackup/license)](https://packagist.org/packages/tp5er/tp5-databackup)
[![PHP Version Require](http://poser.pugx.org/tp5er/tp5-databackup/require/php)](https://packagist.org/packages/tp5er/tp5-databackup)

# tp5-databackup

ThinkPHP 5 数据库备份还原工具，支持队列、命令行、Web 界面等多种使用方式。

> **注意**
> - 本包由 [pkg6](https://github.com/pkg6) 维护，欢迎提交 [pull request](https://github.com/pkg6/think-backup/pulls)。
> - 开发环境 PHP 7.4，兼容 PHP 8+，如有问题请提交 PR 并附上注释。
> - 建议通过队列或命令行方式使用，避免因超时导致备份不完整。

---

## 安装

```bash
composer require tp5er/tp5-databackup
```

---

## 使用方式

### 方式一：自动路由注册

安装后 Service 会自动注册路由，路由前缀通过 `config/backup.php` 中的 `route_prefix` 配置（默认 `backup`）。

访问 `http://yourdomain/backup/index` 即可打开备份管理页面。页面基于 LayUI 渲染，你也可以参考视图文件自行定制前端。

### 方式二：继承 `tp5er\Backup\controller\BackupController`

在 thinkphp 中定义一个控制器继承 `BackupController`：

```php
$controller->index('/custom-prefix'); // 手动指定前缀
$controller->import();                // 自动使用配置中的 route_prefix
```

### 方式三：在 `route/app.php` 中手动注册

```php
<?php
\tp5er\Backup\Route::register();
```

### 方式四：通过队列

**还原数据**

```php
$data["database"] = "mysql";
$data["opt"]      = "import";
$data["filename"] = "fastadmin-mysql-20240416184903.sql";
backup_queue($data);
```

**备份数据 / 修复表 / 优化表**

```php
$data["database"] = "mysql";
$data["opt"]      = "backup"; // backup, repair, optimize
$data["table"]    = ["fa_category", "fa_auth_rule"];
backup_queue($data);
```

### 方式五：通过命令行

```bash
# 进入交互模式进行相关操作
php think backup:choice

# 备份整个数据库表结构和表数据
php think backup:database

# 还原数据
php think backup:import fastadmin-mysql-20240416184903.sql

# 列出所有备份文件
php think backup:list

# 清理缓存
php think backup:cleanup
```

---

## 1.x 升级到 2.x 对照

| 1.x | 2.x | 描述 |
| --- | --- | --- |
| [配置项](https://github.com/pkg6/tp5-databackup/blob/ac29c488bca8a4a123b105ef2959a6cde1f67649/src/Backup.php#L54) | [配置项](https://github.com/pkg6/tp5-databackup/blob/main/config/backup.php) | 配置项 |
| `$db = new \tp5er\Backup\Backup($config)` | 无需初始化，使用 facade 方式 | 初始化 |
| `$db->dataList()` | `Backup::tables()` | 数据表列表 |
| `$db->fileList()` | `Backup::files()` | 备份文件列表 |
| `$db->setFile($file)->backup($tables[$id], 0)` | `Backup::backupStep1($tables)` + `Backup::backupStep2($index, $page)` | 备份表 |
| `$db->setFile($file)->import($start)` | `Backup::import($file)` | 导入表 |
| `delFile($time)` | `unlink($filename)` | 删除备份文件 |
| `downloadFile($time)` | `backup_download($filename)` | 下载备份文件 |
| `$db->repair($tables)` | `Backup::repair($tables)` | 修复表 |
| `$db->optimize($tables)` | `Backup::optimize($tables)` | 优化表 |
| [tp5-demo](https://github.com/pkg6/tp5-databackup/tree/1.x/tp5-demo) | 内置 [控制器](https://github.com/pkg6/tp5-databackup/tree/main/src/controller) 和 [视图](https://github.com/pkg6/tp5-databackup/tree/main/src/views/backup) | 案例代码 |

> **备份流程变化**
> - 1.x：将需备份的表结构写到缓存/cookie 中，根据索引找到表：`$db->setFile($file)->backup($tables[$id], 0)`
> - 2.x：将需备份的表写入缓存，按步骤操作：
>   1. `Backup::backupStep1($tables)` — 缓存待备份表
>   2. `Backup::backupStep2($tableIndex, $page)` — 备份表结构和数据，循环直到 `page = 0`
>   3. `Backup::cleanup()` — 清理缓存

---

## 事件

```php
<?php

namespace tp5er\Backup;

use think\facade\Event as tpEvent;

class Event
{
    const backupStep1     = "tp5er.backup.step1";
    const backupStep2     = "tp5er.backup.step2";
    const backupStep2Data = "tp5er.backup.step2.data";

    public static function event()
    {
        tpEvent::listen(Event::backupStep1, function ($data) {
            /** @var BackupInterface $backup */
            list($backup, $filename, $table, $ret) = $data;
            // Your TODO
        });
        tpEvent::listen(Event::backupStep2, function ($data) {
            /** @var BackupInterface $backup */
            list($backup, $filename, $table) = $data;
            // Your TODO
        });
        tpEvent::listen(Event::backupStep2Data, function ($data) {
            /** @var BackupInterface $backup */
            list($backup, $filename, $sql, $lastPageOrIsBackupData) = $data;
            // Your TODO
        });
    }
}
```

---

## 基础概念

- **reader**：定义读取 SQL 的方法，目前支持 Mysql。自定义需实现 `tp5er\Backup\reader\ReaderInterface`。
- **writer**：定义写入 SQL 的方法，目前支持 File。自定义需实现 `tp5er\Backup\writer\WriterInterface`。

---

## 配置项

`config/backup.php`:

```php
<?php

return [
    'path'              => runtime_path() . 'backup' . DIRECTORY_SEPARATOR, // 备份文件存储目录
    'limit'             => 100,                                             // 每页备份数据量
    'drop_sql'          => true,                                            // 是否生成 DROP TABLE 语句
    'before_import_sql' => [],                                              // 导入前执行的 SQL
    'route_prefix'      => 'backup',                                        // 路由前缀
];
```

---

## 版本记录

[CHANGELOG.md](https://github.com/pkg6/think-backup/blob/main/CHANGELOG.md)

---

## 参与贡献

1. Fork 本仓库到你自己的账号
2. 创建功能分支（如 `develop`）
3. 提交 [pull request](https://github.com/pkg6/think-backup/pulls)
4. 等待作者合并

> 版本号规范：`2.1.x` 表示新方法/新类，`2.x.x` 表示破坏性变更。

## 分支说明

| 分支 | 说明 |
| --- | --- |
| `main` | 2.x 最新代码，tag `2.x` 在此分支 |
| `1.x` | 1.x 最新代码，tag `1.x` 在此分支 |
| `develop` | 2.x 开发分支 |

## 本地开发

```bash
composer create-project topthink/think tp
cd tp
composer require tp5er/tp5-databackup dev-develop
rm -rf vendor/tp5er/tp5-databackup
git clone -b develop git@github.com:pkg6/tp5-databackup.git vendor/tp5er/tp5-databackup
```

进入 `vendor/tp5er/tp5-databackup` 修改代码，提交后发起 pull request 合并到 main 分支。

---

## 其他备份方式

### MySQL 常用命令

```bash
# 备份整个数据库
mysqldump -uroot -hhost -ppassword dbname > backdb.sql

# 备份数据库中的某个表
mysqldump -uroot -hhost -ppassword dbname tbname1 tbname2 > backdb.sql

# 备份多个数据库
mysqldump -uroot -hhost -ppassword --databases dbname1 dbname2 > backdb.sql

# 备份所有数据库
mysqldump -uroot -hhost -ppassword --all-databases > backdb.sql

# 恢复
mysql -uroot -p'123456' dbname < backdb.sql

# 远程备份
mysqldump -h 192.168.3.10 -u root -p123456 test > test.sql

# 远程还原
mysql -h 192.168.3.11 -P 3306 -u root -p123456 test < test.sql
```

### 备份 Shell 脚本

```bash
#!/bin/bash

# 保存备份个数，备份 31 天数据
number=31
# 备份保存路径
backup_dir=/root/mysqlbackup
# 日期
dd=$(date +%Y-%m-%d-%H-%M-%S)
# 备份工具
tool=mysqldump
# 用户名
username=root
# 密码
password=123456
# 将要备份的数据库
database_name=demo

# 如果文件夹不存在则创建
if [ ! -d $backup_dir ]; then
    mkdir -p $backup_dir
fi

# 执行备份
$tool -u $username -p$password $database_name > $backup_dir/$database_name-$dd.sql

# 写入创建备份日志
echo "create $backup_dir/$database_name-$dd.sql" >> $backup_dir/log.txt

# 找出需要删除的备份
delfile=$(ls -l -crt $backup_dir/*.sql | awk '{print $9}' | head -1)

# 判断现在的备份数量是否大于 $number
count=$(ls -l -crt $backup_dir/*.sql | awk '{print $9}' | wc -l)

if [ $count -gt $number ]; then
    # 删除最早生成的备份，只保留 number 数量的备份
    rm $delfile
    echo "delete $delfile" >> $backup_dir/log.txt
fi
```
