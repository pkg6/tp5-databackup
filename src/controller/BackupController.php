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

namespace tp5er\Backup\controller;

use think\facade\View;

/**
 * Class BackupController
 * composer require topthink/think-view
 * /index/backup 使用layui 实现备份的流程
 * /index/import 使用layui 实现还原的流程.
 */
class BackupController
{
    use Controller;

    protected $prefix = "/index";

    protected function fetch($name)
    {
        $routes = array_merge($this->apiRoutes($this->prefix), [
            'view_backup' => $this->prefix . '/index',
            'view_import' => $this->prefix . '/import',
        ]);
        \tp5er\Backup\View::view($routes);

        return View::fetch($name);
    }

    /**
     * 备份视图渲染.
     *
     * @return string
     */
    public function index()
    {
        return $this->fetch('backup/backup');
    }

    /**
     * 还原视图渲染.
     *
     * @return string
     */
    public function import()
    {
        return $this->fetch('backup/import');
    }

}
