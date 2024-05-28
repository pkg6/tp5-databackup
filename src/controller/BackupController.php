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

/**
 * Class BackupController
 * composer require topthink/think-view
 * /index/backup 使用layui 实现备份的流程
 * /index/import 使用layui 实现还原的流程.
 */
class BackupController
{
    use Controller;
    protected function apiPrefix()
    {
        return "/index";
    }
}
