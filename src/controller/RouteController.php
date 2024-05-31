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
use tp5er\Backup\Route;

/**
 * Class RouteController.
 *
 * @see Route
 */
class RouteController
{
    use Controller;

    protected function fetch($name)
    {
        View::config([
            'view_path' => vendor_backup_path('views' . DIRECTORY_SEPARATOR)
        ]);
        View::assign("routes", array_merge($this->apiRoutes(Route::apiPrefix), [
            'view_backup' => Route::prefix . '/backup',
            'view_import' => Route::prefix . '/import',
        ]));

        return View::fetch($name);
    }

    /**
     * 备份视图渲染.
     *
     * @return string
     */
    public function backup()
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
