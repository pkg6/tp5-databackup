<?php

namespace tp5er\Backup\controller;

use tp5er\Backup\Route;

class RouteController extends Controller
{
    protected function apiPrefix()
    {
        return Route::apiPrefix;
    }
}