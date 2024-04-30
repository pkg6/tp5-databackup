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

if ( ! function_exists('mkdirs')) {

    /**
     * 创建目录.
     *
     * @param $path
     *
     * @return void
     */
    function mkdirs($path)
    {
        if ( ! file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }
}

if ( ! function_exists('format_bytes')) {
    /**
     * 格式化字节大小.
     *
     * @param number $size 字节数
     * @param string $delimiter 数字和单位分隔符
     *
     * @return string            格式化后的带单位的大小
     */
    function format_bytes($size, $delimiter = '')
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $size >= 1024 && $i < 5; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . $delimiter . $units[$i];
    }
}

if ( ! function_exists('backup_queue')) {

    /**
     * 通过队列的方式进行备份与还原
     * 使用流程: 参考topthink/think-queue,这里值只需要组装好需要的数据即可.
     *
     * @param array $data
     * @param int $delay
     * @param string $queue
     *
     * @return void
     *
     * @see \tp5er\Backup\validate\BackupValidate
     */
    function backup_queue($data = [], $delay = 0, $queue = null)
    {

        if (backup_validate($data)) {
            $queueObject = app()->get("queue");
            if ($delay > 0) {
                $queueObject->later($delay, \tp5er\Backup\task\Job::class, $data, $queue);
            } else {
                $queueObject->push(\tp5er\Backup\task\Job::class, $data, $queue);
            }
        }

    }
}

if ( ! function_exists('backup_validate')) {

    function backup_validate($data = [])
    {
        $validate = new \tp5er\Backup\validate\BackupValidate();
        if ( ! $validate->scene($data["opt"])->check($data)) {
            throw new \think\exception\ValidateException($validate->getError());
        }

        return true;
    }
}

if ( ! function_exists('backup_run')) {
    /**
     * @param $data
     *
     * @return array|int
     *
     * @throws \tp5er\Backup\exception\ClassDefineException
     * @throws \tp5er\Backup\exception\LockException
     *
     * @see backup_validate
     */
    function backup_run($data)
    {
        $backup = \tp5er\Backup\facade\Backup::database($data["database"]);
        switch ($data['opt']) {
            case \tp5er\Backup\OPT::backup:
                return $backup->backup($data['tables']);
            case \tp5er\Backup\OPT::import:
                return $backup->import($data['filename']);
            case \tp5er\Backup\OPT::repair:
                return  $backup->repair($data['tables']);
            case \tp5er\Backup\OPT::optimize:
                return  $backup->optimize($data['tables']);
            default:
                throw new \tp5er\Backup\exception\TaskException($data);
        }
    }
}
