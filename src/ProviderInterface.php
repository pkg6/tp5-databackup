<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup;

use think\db\ConnectionInterface;

interface ProviderInterface
{
    /**
     * 设置备份目录.
     *
     * @param $path
     *
     * @return mixed
     */
    public function setPath($path);

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
     * @param null $tables
     *
     * @return mixed
     */
    public function optimize($tables = null);

    /**
     * 修复表.
     *
     * @param null $tables
     *
     * @return int
     */
    public function repair($tables = null);

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
     * @param int $offset 向量
     * @param bool $annotation 是否加入注释
     *
     * @return int
     */
    public function writeTableData($table, $limit, $offset, $annotation = true);

    /**
     * 获取所有已经备份好的文件.
     *
     * @return array
     */
    public function files();

    /**
     * 导入sql语句.
     *
     * @param $sql
     *
     * @return mixed
     */
    public function import($sql);

    /**
     * 生成文件名.
     *
     * @param $database
     * @param $connectionName
     *
     * @return string
     */
    public function generateFileName($database, $connectionName);

    /**
     * 生成完整路径.
     *
     * @param $filename
     *
     * @return string
     */
    public function generateFullPathFile($filename);

    /**
     * 将文件名进行切割得到Database ConnectionName,日期+文件后缀,文件后缀
     *
     * @param $fileName
     *
     * @return array
     */
    public function fileNameDatabaseConnectionNameExt($fileName);

}
