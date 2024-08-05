## 2.2以上版本

1. Backup.php文件正式删除
2. 基于2.1版本`BackupInterface` 进行重构底层
3. 移除 `build`，`provider`，`FileInfo`，`FileName`类
4. 添加`Writer`，`reader`类
5. 将 `tp5er\Backup\controller\ApiController` 更改为 `tp5er\Backup\controller\BackupController`
6. 定义接口`\tp5er\Backup\Route::api()`加入到`route/app.php`，用户可以自定义前端，直接调用接口即可
7. 添加删除备份文件接口
8. 移除response添加自定义函数`backup_success`和 `backup_error`
9. `src/validate `目录下文件名修改
10. 移除不必要的异常
11. [将导入功能标记废弃](https://github.com/pkg6/tp5-databackup/issues/90)
12. 封装SQLFormat来进行组装成需要的SQL语句
13. 目前可以在第三方导入平台进行导入，phpmyadmin，navicat 同时也是2.x版本主要维护
14. 根据配置`drop_sql`来判断是否加入`DROP TABLE IF EXISTS table `SQL语句
15. 根据`layui`配置家在前端（用于测试）资源文件
16. topthink/think-view `^1.0 || ^2.0`

## 2.1以上版本

1. Backup.php文件未被删除，只是标记为废弃，在2.2.x版本直接删除。
2. 重写数据库优化表，修复表，导入，导出等功能实现。更多可参考[README.md](https://github.com/pkg6/tp5-databackup/blob/main/README.md)

## 2.0版本
将topthink/framework加入到composer

## 1.1.x版本

1. 修改命名空间从`\tp5er\Backup`到`\tp5er\Backup\Backup`
2. 支持thinkphp5,thinkphp5.1,thinkphp6

## 1.0.x版本

支持thinkphp5,thinkphp5.1,thinkphp6

## 0.1.0版本

支持thinkphp5 和 thinkphp5.1

## 0.0.1版本

支持thinkphp5

