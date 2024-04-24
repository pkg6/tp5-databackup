<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\controller;

interface ControllerInterface
{
    /**
     * 获取所有的数据表
     * /index/tables.
     *
     * @return \think\Response
     */
    public function tables();

    /**
     * 获取所有备份文件
     * /index/filelist.
     *
     * @return \think\Response
     */
    public function filelist();

    /**
     * 导入
     * /index/import?file=fastadmin-mysql-20240416184903.sql.
     * 文件过大会导致出现接口超时，读取失败等问题,推荐使用队列进行导入/命令行进行导入.
     *
     * @return \think\Response
     */
    public function import();

    /**
     * 备份第一步
     * 提交备份任务：/index/backupStep1发送post请求，数据格式`{ "tables": ["admin","log"]}` 响应`['index' => 0, 'page' => 1]`.
     *
     * @return \think\Response
     */
    public function backupStep1();

    /**
     * 备份第二步
     * 发送备份数据请求：/index/export发送get请求/index/backupStep2?index=0&page=0,直到page=0表示该数据备份完成.
     *
     * @return \think\Response
     */
    public function backupStep2();

    /**
     * 整个库备份完之后清理缓存
     * /index/cleanup.
     *
     * @return \think\Response
     *
     * @see export
     */
    public function cleanup();

    /**
     * 修复表
     * /index/repair.
     *
     * @return \think\Response
     */
    public function repair();

    /**
     * 优化表
     * /index/optimize.
     *
     * @return \think\Response
     */
    public function optimize();

}
