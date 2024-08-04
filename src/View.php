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

class View
{
    /**
     * https://purge.jsdelivr.net/gh/pkg6/tp5-databackup@main/src/tp5er.js.
     * https://cdn.jsdelivr.net/gh/pkg6/tp5-databackup@main/src/tp5er.js.
     *
     *
     * https://raw.githubusercontent.com/pkg6/tp5-databackup/main/src/tp5er.js.
     *
     * @param $routes
     *
     * @return void
     */
    public static function view($routes)
    {
        \think\facade\View::config([
            'view_path' => vendor_backup_path('views' . DIRECTORY_SEPARATOR)
        ]);
        \think\facade\View::assign("routes", $routes);
        $tp5erjs = config('backup.layui.tp5erjs', 'https://raw.githubusercontent.com/pkg6/tp5-databackup/main/src/tp5er.js');
        $layuijs = config('backup.layui.layuijs', '//unpkg.com/layui@2.9.8/dist/layui.js');
        $layuicss = config('backup.layui.layuicss', '//cdn.staticfile.org/layui/2.9.7/css/layui.css');
        \think\facade\View::assign("layui", compact('tp5erjs', 'layuicss', 'layuijs'));
    }
}
