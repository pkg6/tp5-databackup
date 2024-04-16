<?php

namespace tp5er\Backup;

interface SQLInterface
{
    /**
     * 获取所有表
     * @return string
     */
    public function tables();

    /**
     * 优化表
     * 需要判断table如果是字符串表示单表操作，如果是数组就是多表操作
     * @param $table
     * @return string
     */
    public function optimize($table = null);

    /**
     * 修复表
     * 需要判断table如果是字符串表示单表操作，如果是数组就是多表操作
     * @param $table
     * @return mixed
     */
    public function repair($table = null);
}