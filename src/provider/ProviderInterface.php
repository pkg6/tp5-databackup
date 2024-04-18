<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\provider;

use think\db\ConnectionInterface;
use tp5er\Backup\build\BuildSQLInterface;
use tp5er\Backup\FileName;
use tp5er\Backup\write\WriteAbstract;

interface ProviderInterface
{

    /**
     * @param FileName $fileName
     *
     * @return $this
     */
    public function setFileName(FileName $fileName);

    /**
     * 设置写入方式.
     *
     * @param WriteAbstract $write
     *
     * @return $this
     */
    public function setWrite(WriteAbstract $write);

    /**
     * 设置数据库连接.
     *
     * @param ConnectionInterface $connection
     *
     * @return $this
     */
    public function setConnection(ConnectionInterface $connection);

    /**
     * 设置基本的数据查询方式.
     *
     * @param BuildSQLInterface $buildSQL
     *
     * @return $this
     */
    public function setBuildSQL(BuildSQLInterface $buildSQL);

    /**
     * 获取所有数据表.
     *
     * @return mixed
     */
    public function tables();

    /**
     * 优化表.
     *
     * @param string|array $tables
     *
     * @return mixed
     */
    public function optimize($tables);

    /**
     * 修复表.
     *
     * @param string|array $tables
     *
     * @return int
     */
    public function repair($tables);

    /**
     * 写入表结构.
     *
     * @param $table
     *
     * @return bool
     */
    public function writeTableStructure($table);

    /**
     * 写入表数据.
     *
     * @param string $table 操作的表
     * @param int $limit 处理数据量
     * @param int $page 分页数量
     * @param bool $annotation 是否加入注释
     *
     * @return int
     */
    public function writeTableData($table, $limit, $page, $annotation = true);

    /**
     * 获取所有已经备份好的文件.
     *
     * @return array
     */
    public function files();

    /**
     * 导入sql语句.
     *
     * @param string|array $sqls
     *
     * @return mixed
     */
    public function import($sqls);

}
