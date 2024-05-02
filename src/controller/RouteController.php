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

use tp5er\Backup\Route;

/**
 * Class RouteController.
 *
 * @see Route
 */
class RouteController extends Controller
{
    protected function apiPrefix()
    {
        return Route::apiPrefix;
    }
}