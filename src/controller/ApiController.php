<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\controller;

use think\facade\View;
use think\helper\Str;
use tp5er\Backup\exception\LockException;
use tp5er\Backup\facade\Backup;
use tp5er\Backup\OPT;
use tp5er\Backup\validate\ExportValidate;

/**
 * 作者是将此控制器继承在Index.php中,所以路由/index/*
 *  composer require topthink/think-view
 * /index/backup 使用layui 实现备份的流程
 * /index/import 使用layui 实现还原的流程
 * Class ApiController.
 */
class ApiController
{
    use Response;

    /**
     * 路由.
     *
     * @return string[]
     */
    protected function apiRoute()
    {
        return [
            "tables" => "/index/tables",
            "optimize" => "/index/optimize",
            "repair" => "/index/repair",
            "backupStep1" => "/index/backupStep1",
            "backupStep2" => "/index/backupStep2",
            "cleanup" => "/index/cleanup",
            "files" => "/index/files",
            "import" => "/index/doImport",
            "download" => "/index/download",
        ];
    }

    /**
     * 备份视图渲染.
     *
     * @return string
     */
    public function backup()
    {
        View::config([
            'view_path' => backup_src_path . 'views' . DIRECTORY_SEPARATOR,
        ]);
        View::assign("routes", $this->apiRoute());

        return View::fetch("backup/backup");
    }

    /**
     * 还原视图渲染.
     *
     * @return string
     */
    public function import()
    {
        View::config([
            'view_path' => backup_src_path . 'views' . DIRECTORY_SEPARATOR,
        ]);
        View::assign("routes", $this->apiRoute());

        return View::fetch("backup/import");
    }

    /**
     * 获取所有的数据表
     * /index/tables.
     *
     * @return \think\Response
     */
    public function tables()
    {
        $list = Backup::tables();
        $ret = [];
        foreach ($list as $k => $item) {
            foreach ($item as $field => $value) {
                $f = Str::snake($field);
                if ($f=="data_length"){
                    $value = format_bytes($value);
                }
                $ret[$k][$f] = $value;
            }
        }

        return $this->success($ret);
    }

    /**
     * 获取所有备份文件
     * /index/filelist.
     *
     * @return \think\Response
     */
    public function files()
    {
        $list = Backup::files();

        return $this->success($list, '拉去本地文件成功');
    }

    /**
     * 导入
     * /index/import?name=fastadmin-mysql-20240416184903.sql.
     * 文件过大会导致出现接口超时，读取失败等问题,推荐使用队列进行导入/命令行进行导入.
     *
     * @return \think\Response
     */
    public function doImport()
    {
        $file = request()->param('name');
        try {
            $ret = Backup::import($file);

            return $this->success($ret, "数据还原成功");
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage());
        }
    }

    /**
     * 备份第一步
     * 提交备份任务：/index/backupStep1发送post请求，数据格式`{ "tables": ["admin","log"]}` 响应`['index' => 0, 'page' => 1]`.
     *
     * @return \think\Response
     */
    public function backupStep1()
    {
        $validate = new ExportValidate();
        $data = request()->post();
        if ( ! $validate->scene("step1")->check($data)) {
            return $this->error($validate->getError());
        }
        try {
            if (Backup::backupStep1($data["tables"])) {
                return $this->success([
                    'index' => 0,
                    'page' => 1,
                    "tables" => $data["tables"],
                ], '初始化成功！');
            } else {
                return $this->error('初始化失败！');
            }
        } catch (LockException $exception) {
            return $this->error('检测到有一个备份任务正在执行，请稍后再试！');
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage());
        }
    }

    /**
     * 备份第二步
     * 发送备份数据请求：/index/export发送get请求/index/backupStep2?index=0&page=0,直到page=0表示该数据备份完成.
     *
     * @return \think\Response
     */
    public function backupStep2()
    {
        $validate = new ExportValidate();
        $data = request()->get();
        if ( ! $validate->scene("step2")->check($data)) {
            return $this->error($validate->getError());
        }
        $index = (int) $data["index"];
        $lastPage = Backup::backupStep2($index, $data["page"]);

        if ($lastPage == 0) {
            return $this->success([
                'index' => $index + 1,
                'page' => 0,
                "table" => Backup::getCurrentBackupTable(),
            ], '单表备份完毕！');
        } else {
            $msg = "需要继续进行备份数据！'";
            if ($lastPage < 0) {
                $msg = OPT::backupPage($lastPage);
            }

            return $this->success([
                'index' => $index,
                'page' => $lastPage,
                "table" => Backup::getCurrentBackupTable()
            ], $msg);
        }
    }

    /**
     * 整个库备份完之后清理缓存
     * /index/cleanup.
     *
     * @return \think\Response
     *
     * @see export
     */
    public function cleanup()
    {
        Backup::cleanup();

        return $this->success([], '整库备份完毕！');
    }

    /**
     * 修复表
     * /index/repair.
     *
     * @return \think\Response
     */
    public function repair()
    {
        $tables = request()->post("tables");
        if (is_null($tables)) {
            return $this->error("没有获取到表");
        }
        if (Backup::repair($tables)) {
            return $this->success($tables, "数据表修复完成！");
        } else {
            return $this->error("数据表修复出错请重试");
        }
    }

    /**
     * 优化表
     * /index/optimize.
     *
     * @return \think\Response
     */
    public function optimize()
    {
        $tables = request()->post("tables");
        if (is_null($tables)) {
            return $this->error("没有获取到表");
        }
        if (Backup::optimize($tables)) {
            return $this->success($tables, "数据表优化完成！");
        } else {
            return $this->error("数据表优化出错请重试！");
        }
    }

    /**
     * 备份文件下载.
     *
     * /index/download?file=fastadmin-mysql-20240416184903.sql.
     *
     * @return mixed
     */
    public function download()
    {
        $filename = request()->param('filename');

        return \think\Response::create($filename, 'file')
            ->name(pathinfo($filename, PATHINFO_BASENAME))
            ->isContent(false)
            ->expire(180);
    }

}
