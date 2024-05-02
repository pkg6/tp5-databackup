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

use tp5er\Backup\reader\ReaderInterface;
use tp5er\Backup\writer\WriterInterface;

interface BackupInterface
{
    const version = "2.2";
    /**
     * 获取你当前使用的版本号.
     *
     * @return string
     */
    public function getVersion();

    /**
     * 获取配置.
     *
     * @param $name
     *
     * @return array
     */
    public function config($name = null);

    /**
     * @param WriterInterface $writer
     *
     * @return $this
     */
    public function setWriter(WriterInterface $writer);

    /**
     * @param $writeType
     *
     * @return WriterInterface
     */
    public function getWriter($writeType = null);

    /**
     * @return string
     */
    public function getCurrentWriterType();

    /**
     * @param ReaderInterface|null $reader
     *
     * @return $this
     */
    public function setReader(ReaderInterface $reader = null);

    /**
     * @param $writeType
     *
     * @return ReaderInterface
     */
    public function getReader($writeType = null);

    /**
     * @return mixed
     */
    public function getCurrentReaderType();
    /**
     * 选中数据库.
     *
     * @param $connectionName
     *
     * @return mixed
     */
    public function database($connectionName = null);

    /**
     * 获取选中的数据库.
     *
     * @return string
     */
    public function getDatabase();
    /**
     * 获取选中数据库的配置.
     *
     * @return array
     */
    public function getDatabaseConfig();

    /**
     * 获取连接名称.
     *
     * @return string
     */
    public function getConnectionName();

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
     * 可作为备份第一步，用于前端进度条
     *
     * @param $tables
     *
     * @return array
     */
    public function tableCounts($tables);
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
     * @return array
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
