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

namespace tp5er\Backup\build;

use think\db\ConnectionInterface;

interface BuildSQLInterface
{
    /**
     * 获取所有表.
     *
     * @param ConnectionInterface $connection
     *
     * @return array
     */
    public function tables(ConnectionInterface $connection);

    /**
     * 获取单表单总数.
     *
     * @param ConnectionInterface $connection
     * @param $table
     *
     * @return mixed
     */
    public function tableCount(ConnectionInterface $connection, $table);

    /**
     * 优化表
     * 需要判断table如果是字符串表示单表操作，如果是数组就是多表操作.
     *
     * @param ConnectionInterface $connection
     * @param null $table
     *
     * @return string
     */
    public function optimize(ConnectionInterface $connection, $table);

    /**
     * 修复表
     * 需要判断table如果是字符串表示单表操作，如果是数组就是多表操作.
     *
     * @param ConnectionInterface $connection
     * @param null $table
     *
     * @return mixed
     */
    public function repair(ConnectionInterface $connection, $table);

    /**
     * @param ConnectionInterface $connection
     * @param $table
     *
     * @return array
     */
    public function tableStructure(ConnectionInterface $connection, $table);

    /**
     * @param ConnectionInterface $connection
     * @param $table
     * @param int $page
     * @param int $limit
     *
     * @return mixed
     */
    public function tableInstert(ConnectionInterface $connection, $table, $page = 0, $limit = 100);

    /**
     * @param ConnectionInterface $connection
     * @param string|array $sqls
     *
     * @return int
     */
    public function execute(ConnectionInterface $connection, $sqls);
}
