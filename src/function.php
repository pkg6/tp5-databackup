<?php

use think\Response;
use tp5er\Backup\exception\BackupException;
use tp5er\Backup\facade\Backup;

if (!function_exists('backup_success')) {
    function backup_success($data = '', $msg = 'success', ?string $url = null, int $wait = 3, array $header = [])
    {
        if (is_null($url) && isset($_SERVER['HTTP_REFERER'])) {
            $url = $_SERVER['HTTP_REFERER'];
        } elseif ($url) {
            $url = (strpos($url, '://') !== false || strpos($url, '/') === 0) ? $url : (string)app()->route->buildUrl($url);
        }

        return Response::create([
            'code' => 0,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ], 'json')->header($header);
    }
}

if (!function_exists('backup_error')) {
    function backup_error($msg = '', ?string $url = null, $data = '', int $wait = 3, array $header = [])
    {
        if (is_null($url)) {
            $url = app()->request->isAjax() ? '' : 'javascript:history.back(-1);';
        } elseif ($url) {
            $url = (strpos($url, '://') !== false || strpos($url, '/') === 0) ? $url : (string)app()->route->buildUrl($url);
        }

        return Response::create([
            'code' => 1,
            'msg' => $msg,
            'data' => $data,
            'url' => $url,
            'wait' => $wait,
        ], 'json')->header($header);
    }
}

if (!function_exists('backup_download')) {
    function backup_download($filename)
    {
        return Response::create($filename, 'file')
            ->name(pathinfo($filename, PATHINFO_BASENAME))
            ->isContent(false)
            ->expire(180);
    }
}

if (!function_exists('format_bytes')) {
    function format_bytes($size, $delimiter = '')
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $size >= 1024 && $i < 5; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . $delimiter . $units[$i];
    }
}

if (!function_exists('backup_queue')) {
    function backup_queue($data = [], $delay = 0, $queue = null)
    {
        if (backup_validate($data)) {
            $queueObject = app()->get('queue');
            if ($delay > 0) {
                $queueObject->later($delay, \tp5er\Backup\task\Job::class, $data, $queue);
            } else {
                $queueObject->push(\tp5er\Backup\task\Job::class, $data, $queue);
            }
        }
    }
}

if (!function_exists('backup_validate')) {
    function backup_validate($data = [])
    {
        $validate = new \think\Validate();
        $validate
            ->rule('opt', 'require|in:import,backup,repair,optimize,drop,truncate,prefixChange')
            ->rule('database', 'require')
            ->message([
                'opt.require' => '操作不能为空',
                'opt.in' => '操作类型错误',
                'database.require' => '数据库连接不能为空',
            ]);
        if (isset($data['opt']) && in_array($data['opt'], ['import'])) {
            $validate->rule('filename', 'require')->message(['filename.require' => '文件名不能为空']);
        } else {
            $validate->rule('tables', 'require')->message(['tables.require' => '表数据不能为空']);
        }
        if (!$validate->check($data)) {
            throw new \think\exception\ValidateException($validate->getError());
        }

        return true;
    }
}

if (!function_exists('backup_run')) {
    function backup_run($data)
    {
        $backup = Backup::database($data['database'] ?? null);
        switch ($data['opt']) {
            case 'backup':
                return $backup->backup($data['tables']);
            case 'import':
                return $backup->import($data['filename']);
            case 'repair':
                return $backup->repair($data['tables']);
            case 'optimize':
                return $backup->optimize($data['tables']);
            case 'drop':
                return $backup->drop($data['tables']);
            case 'truncate':
                return $backup->truncate($data['tables']);
            case 'prefixChange':
                return $backup->prefixChange($data['tables']);
            default:
                throw new BackupException('Task execution failed');
        }
    }

}
