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
use think\helper\Str;
use tp5er\Backup\exception\LockException;
use tp5er\Backup\facade\Backup;
use tp5er\Backup\OPT;
use tp5er\Backup\Route;
use tp5er\Backup\validate\WebValidate;

/**
 * 作者是将此控制器继承在Index.php中,所以路由/index/*
 * Class ApiController.
 */
trait Controller
{

    /**
     * @var string
     */
    protected $viewbackup = 'backup/backup';
    /**
     * @var string
     */
    protected $viewimport = 'backup/import';

    /**
     * @return string
     */
    abstract protected function apiPrefix();

    /**
     * @param $view
     * @return string
     */
    protected function backupFetch($view)
    {
        $prefix = $this->apiPrefix();

        if ($prefix === Route::apiPrefix) {
            $view_backup=Route::prefix . '/backup';
            $view_import=Route::prefix . '/import';
        }else{
            $view_backup=$prefix . '/backup';
            $view_import=$prefix . '/import';
        }
        $routes = [
            'view_backup' => $view_backup,
            'view_import' => $view_import,

            "tables"      => $prefix . "/tables",
            "optimize"    => $prefix . "/optimize",
            "repair"      => $prefix . "/repair",
            "backupStep1" => $prefix . "/backupStep1",
            "backupStep2" => $prefix . "/backupStep2",
            "cleanup"     => $prefix . "/cleanup",
            "files"       => $prefix . "/files",
            "import"      => $prefix . "/doImport",
            "download"    => $prefix . "/download",
            "delete"      => $prefix . "/delete",
        ];
        View::config([
            'view_path' => vendor_backup_path('views' . DIRECTORY_SEPARATOR)
        ]);
        View::assign("routes", $routes);
        return View::fetch($view);
    }

    /**
     * 备份视图渲染.
     *
     * @return string
     */
    public function backup()
    {
        return $this->backupFetch($this->viewbackup);
    }

    /**
     * 还原视图渲染.
     *
     * @return string
     */
    public function import()
    {
        return $this->backupFetch($this->viewimport);
    }

    /**
     * @return \tp5er\Backup\BackupInterface
     */
    protected function databaseBackup()
    {
        return Backup::database();
    }

    /**
     * 获取所有的数据表
     * /index/tables.
     *
     * @return \think\Response
     */
    public function tables()
    {
        $list = $this->databaseBackup()->tables();
        $ret  = [];
        foreach ($list as $k => $item) {
            foreach ($item as $field => $value) {
                $f = Str::snake($field);
                if ($f == "data_length") {
                    $value = format_bytes($value);
                }
                $ret[$k][$f] = $value;
            }
        }

        return backup_success($ret);
    }

    /**
     * 获取所有备份文件.
     *
     * @return \think\Response
     */
    public function files()
    {
        $list = $this->databaseBackup()->files();

        return backup_success($list, '拉去本地文件成功');
    }

    /**
     * 导入
     * 文件过大会导致出现接口超时，读取失败等问题,推荐使用队列进行导入/命令行进行导入.
     *
     * @return \think\Response
     */
    public function doImport()
    {
        $file = request()->param('name');
        try {
            $ret = $this->databaseBackup()->import($file);

            return backup_success($ret, "数据还原成功");
        } catch (\Exception $exception) {
            return backup_error($exception->getMessage());
        }
    }

    /**
     * 备份第一步
     * 提交备份任务：/backupStep1发送post请求，数据格式`{ "tables": ["admin","log"]}` 响应`['index' => 0, 'page' => 1]`.
     *
     * @return \think\Response
     */
    public function backupStep1()
    {
        $validate = new WebValidate();
        $data     = request()->post();
        if (!$validate->scene("step1")->check($data)) {
            return backup_error($validate->getError());
        }
        try {
            if ($this->databaseBackup()->backupStep1($data["tables"])) {
                return backup_success([
                    'index'  => 0,
                    'page'   => 1,
                    "tables" => $data["tables"],
                ], '初始化成功！');
            } else {
                return backup_error('初始化失败！');
            }
        } catch (LockException $exception) {
            return backup_error('检测到有一个备份任务正在执行，请稍后再试！');
        } catch (\Exception $exception) {
            return backup_error($exception->getMessage());
        }
    }

    /**
     * 可作为备份第一步，用于前端进度统计.
     *
     * @return \think\Response
     */
    public function tableCounts()
    {
        $validate = new WebValidate();
        $data     = request()->post();
        if (!$validate->scene("step1")->check($data)) {
            return backup_error($validate->getError());
        }
        try {
            $ret = $this->databaseBackup()->tableCounts($data["tables"]);
            if ($ret) {
                return backup_success([
                    'index'  => 0,
                    'page'   => 1,
                    "tables" => $ret,
                ], '初始化成功！');
            } else {
                return backup_error('初始化失败！');
            }
        } catch (LockException $exception) {
            return backup_error('检测到有一个备份任务正在执行，请稍后再试！');
        } catch (\Exception $exception) {
            return backup_error($exception->getMessage());
        }
    }

    /**
     * 备份第二步
     * 发送备份数据请求：/export发送get请求/backupStep2?index=0&page=0,直到page=0表示该数据备份完成.
     *
     * @return \think\Response
     */
    public function backupStep2()
    {
        $validate = new WebValidate();
        $data     = request()->get();
        if (!$validate->scene("step2")->check($data)) {
            return backup_error($validate->getError());
        }
        $index    = (int)$data["index"];
        $lastPage = $this->databaseBackup()->backupStep2($index, $data["page"]);

        if ($lastPage == 0) {
            return backup_success([
                'index' => $index + 1,
                'page'  => 0,
                "table" => Backup::getCurrentBackupTable(),
            ], '单表备份完毕！');
        } else {
            $msg = "需要继续进行备份数据！'";
            if ($lastPage < 0) {
                $msg = OPT::backupPage($lastPage);
            }

            return backup_success([
                'index' => $index,
                'page'  => $lastPage,
                "table" => $this->databaseBackup()->getCurrentBackupTable()
            ], $msg);
        }
    }

    /**
     * 整个库备份完之后清理缓存
     * /cleanup.
     *
     * @return \think\Response
     *
     * @see export
     */
    public function cleanup()
    {
        $this->databaseBackup()->cleanup();

        return backup_success([], '整库备份完毕！');
    }

    /**
     * 修复表
     * /repair.
     *
     * @return \think\Response
     */
    public function repair()
    {
        $tables = request()->post("tables");
        if (is_null($tables)) {
            return backup_error("没有获取到表");
        }
        if ($this->databaseBackup()->repair($tables)) {
            return backup_success($tables, "数据表修复完成！");
        } else {
            return backup_error("数据表修复出错请重试");
        }
    }

    /**
     * 优化表
     * /optimize.
     *
     * @return \think\Response
     */
    public function optimize()
    {
        $tables = request()->post("tables");
        if (is_null($tables)) {
            return backup_error("没有获取到表");
        }
        if ($this->databaseBackup()->optimize($tables)) {
            return backup_success($tables, "数据表优化完成！");
        } else {
            return backup_error("数据表优化出错请重试！");
        }
    }

    /**
     * 备份文件下载.
     *
     * /download?file=fastadmin-mysql-20240416184903.sql.
     *
     * @return mixed
     */
    public function download()
    {
        $filename = request()->param('filename');

        return backup_download($filename);
    }

    /**
     * 删除备份文件.
     *
     * @return \think\Response
     */
    public function delete()
    {
        $filename = request()->param('filename');
        unlink($filename);

        return backup_success("", "删除成功");
    }
}
