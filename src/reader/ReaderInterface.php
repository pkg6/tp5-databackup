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

namespace tp5er\Backup\reader;

use think\App;
use think\db\ConnectionInterface;
use tp5er\Backup\BackupInterface;

interface ReaderInterface
{

    /**
     * @param App $app
     *
     * @return mixed
     */
    public function setApp(App $app);

    /**
     * @param $config
     *
     * @return mixed
     */
    public function setConfig($config);

    /**
     * @return string
     */
    public function type();

    /**
     * 设置数据库连接.
     *
     * @param ConnectionInterface $connection
     *
     * @return $this
     */
    public function setConnection(ConnectionInterface $connection);

    /**
     * 初始化sql
     * $sql = 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL . PHP_EOL;
     * $sql .= 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL;.
     *
     * @return string
     */
    public function copyright(BackupInterface $backup);

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
     * @return  []
     * 返回数组 第一个 生成sql语句，第二个是否备份数据
     */
    public function tableStructure($table);

    /**
     * 写入表数据.
     *
     * @param string $table 操作的表
     * @param int $limit 处理数据量
     * @param int $page 分页数量
     * @param bool $annotation 是否加入注释
     *
     * @return array
     * 返回 第一个生成sql语句 第二个下一个向量
     */
    public function tableData($table, $limit, $page, $annotation = true);

    /**
     * 导入sql语句.
     *
     * @param string|array $sqls
     *
     * @return mixed
     */
    public function import($sqls);

}
