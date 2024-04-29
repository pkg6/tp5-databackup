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

namespace tp5er\Backup;

use tp5er\Backup\provider\ProviderInterface;
use tp5er\Backup\write\WriteAbstract;

interface BackupInterface
{

    /**
     * @param WriteAbstract $write
     *
     * @return $this
     */
    public function setWrite(WriteAbstract $write);

    /**
     * 获取你当前使用的版本号.
     *
     * @return string
     */
    public function getVersion();

    /**
     * @param ProviderInterface|null $provider
     *
     * @return $this
     */
    public function setProvider(ProviderInterface $provider = null);

    /**
     * @param $connection
     * 为null的时候读取database.php默认的配置
     * 为字符串时候，读取自定义链接信息
     * 为ConnectionInterface时候就走用户自定义
     * @param $writeType
     *
     * @return ProviderInterface
     */
    public function getProviderObject($connection = null, $writeType = null);

    /**
     * 选中数据库.
     *
     * @param $connectionName
     *
     * @return mixed
     */
    public function database($connectionName = null);

    /**
     * 拉去所有的数据表.
     *
     * @return mixed
     */
    public function tables();

    /**
     * 优化表.
     *
     * @param array|string $tables
     *
     * @return mixed
     */
    public function optimize($tables = null);

    /**
     * 修复表.
     *
     * @param array|string $tables
     *
     * @return mixed
     */
    public function repair($tables);

    /**
     * 分步备份第一步.
     *
     * @param array $tables
     *
     * @return mixed
     */
    public function backupStep1(array $tables);

    /**
     * 分步备份第二步.
     *
     * @param int $index
     * @param int $page
     *
     * @return mixed
     */
    public function backupStep2($index = 0, $page = 0);

    /**
     * 当前备份的文件名.
     *
     * @return string
     */
    public function getCurrentBackupFile();

    /**
     * 当前备份正在备份的表.
     *
     * @return string
     */
    public function getCurrentBackupTable();
    /**
     * 备份所有表中结构和数据.
     *
     * @param array $tables
     *
     * @return mixed
     */
    public function backup($tables);

    /**
     * @return void
     */
    public function cleanup();

    /**
     * 备份文件列表.
     *
     * @return FileInfo[]
     */
    public function files();

    /**
     * 导入.
     *
     * @param $fileName
     *
     * @return mixed
     */
    public function import($fileName);
}
