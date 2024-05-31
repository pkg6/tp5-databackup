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

if ( ! function_exists('backup_success')) {

    /**
     * 响应成功
     *
     * @param string $data
     * @param string $msg
     * @param string|null $url
     * @param int $wait
     * @param array $header
     *
     * @return \think\Response
     */
    function backup_success($data = '', $msg = 'success', string $url = null, int $wait = 3, array $header = [])
    {
        if (is_null($url) && isset($_SERVER["HTTP_REFERER"])) {
            $url = $_SERVER["HTTP_REFERER"];
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string) app()->route->buildUrl($url);
        }
        $result = [
            'code' => 0,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ];

        return \think\Response::create($result, "json")->header($header);
    }
}

if ( ! function_exists('backup_error')) {

    /**
     * 响应错误.
     *
     * @param string $msg
     * @param string|null $url
     * @param string $data
     * @param int $wait
     * @param array $header
     *
     * @return \think\Response
     */
    function backup_error($msg = '', string $url = null, $data = '', int $wait = 3, array $header = [])
    {
        if (is_null($url)) {
            $url = app()->request->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ($url) {
            $url = (strpos($url, '://') || 0 === strpos($url, '/')) ? $url : (string) app()->route->buildUrl($url);
        }

        return \think\Response::create([
            'code' => 1,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ], "json")->header($header);
    }
}

if ( ! function_exists('download')) {

    /**
     * 文件下载.
     *
     * @param $filename
     *
     * @return \think\Response
     */
    function backup_download($filename)
    {
        return \think\Response::create($filename, 'file')
            ->name(pathinfo($filename, PATHINFO_BASENAME))
            ->isContent(false)
            ->expire(180);
    }
}

if ( ! function_exists('mkdirs')) {

    /**
     * 创建目录.
     *
     * @param $path
     *
     * @return bool
     */
    function mkdirs($path)
    {
        if ( ! file_exists($path)) {
            return mkdir($path, 0755, true);
        }

        return true;
    }
}
if ( ! function_exists('databackup_version')) {
    /**
     * 当前安装tp5er/tp5-databackup版本号.
     *
     * @return string
     */
    function databackup_version()
    {
        $composer = json_decode(file_get_contents(app()->getRootPath() . "composer.json"), true);

        return \think\helper\Arr::get($composer, "require.tp5er/tp5-databackup");
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
     * @see \tp5er\Backup\validate\QueueValidate
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
        $validate = new \tp5er\Backup\validate\QueueValidate();
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
                return $backup->repair($data['tables']);
            case \tp5er\Backup\OPT::optimize:
                return $backup->optimize($data['tables']);
            default:
                throw new \tp5er\Backup\exception\TaskException($data);
        }
    }
}


if ( ! function_exists('vendor_backup_path')) {
    /**
     * @param $path
     * @return string
     */
    function vendor_backup_path($path)
    {
        return __DIR__ . DIRECTORY_SEPARATOR.$path;
    }
}

if (!function_exists('yield_path')) {
    /**
     * @param $path
     * @return RecursiveIteratorIterator
     */
    function yield_path($path)
    {
        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path),
            \RecursiveIteratorIterator::SELF_FIRST
        );
    }
}
