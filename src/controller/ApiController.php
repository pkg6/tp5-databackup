<?php

namespace tp5er\Backup\controller;


use tp5er\Backup\exception\FileException;
use tp5er\Backup\exception\LockException;
use tp5er\Backup\facade\Backup;
use tp5er\Backup\Response;
use tp5er\Backup\validate\ExportValidate;

class ApiController
{
    use Response;

    //http://127.0.0.1:8000/index/tables
    public function tables()
    {
        $list = Backup::tables();
        return $this->success($list);
    }

    /**
     * http://127.0.0.1:8000/index/filelist
     * @return \think\Response
     */
    public function filelist()
    {
        $list = Backup::fileList();
        return $this->success($list, '拉去本地文件成功');
    }


    /**
     * 导入
     * http://127.0.0.1:8000/index/import?file=fastadmin-mysql-20240416184903.sql
     * @return \think\Response
     */
    public function import()
    {
        $file = request()->param('file');
        try {
            $ret = Backup::import($file);
            return $this->success($ret, "数据还原成功");
        } catch (\Exception $exception) {
            return $this->error($exception->getMessage());
        }
    }

    //导出
    //http://127.0.0.1:8000/index/export
    public function export()
    {
        $validate = new ExportValidate();
        if (request()->isPost()) {
            $data = request()->post();
            if (!$validate->scene("step1")->check($data)) {
                return $this->error($validate->getError());
            }
            try {
                if (Backup::apiBackupStep1($data["tables"])) {
                    return $this->success(['index' => 0, 'offset' => 1], '初始化成功！');
                }
            } catch (LockException $exception) {
                return $this->error('检测到有一个备份任务正在执行，请稍后再试！');
            }
        } elseif (request()->isGet()) {
            $data = request()->get();
            if (!$validate->scene("step2")->check($data)) {
                return $this->error($validate->getError());
            }
            $index      = $data["index"];
            $lastOffset = Backup::apiBackupStep2($index, $data["offset"]);
            if ($lastOffset == 0) {
                return $this->success(['index' => $index + 1, 'offset' => $lastOffset], '备份完毕！');
            } else {
                return $this->success(['index' => $index, 'offset' => $lastOffset], '需要继续进行备份数据！');
            }
        }
    }


    //修复表
    public function repair()
    {
        $tables = request()->param("tables");
        if (Backup::repair($tables)) {
            return $this->success("数据表修复完成！");
        } else {
            return $this->error("数据表修复出错请重试");
        }
    }

    //优化表
    public function optimize()
    {
        $tables = request()->param("tables");
        if (Backup::optimize($tables)) {
            return $this->success("数据表优化完成！");
        } else {
            return $this->error("数据表优化出错请重试！");
        }
    }

}