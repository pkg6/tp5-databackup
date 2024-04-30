
## 2.2以上版本

1.  Backup.php文件正式删除

2. 基于2.1版本`BackupInterface` 进行重构底层
3. 移除 `build`，`provider`，`FileInfo`，`FileName`类
4. 添加`Writer`，`reader`类

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

