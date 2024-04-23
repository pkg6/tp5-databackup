<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\controller;

use tp5er\Backup\exception\LockException;
use tp5er\Backup\facade\Backup;
use tp5er\Backup\validate\ExportValidate;

/**
 * 作者是将此控制器继承在Index.php中,所以路由/index/*
 * Class ApiController.
 */
class ApiController
{
    use Response;

    /**
     * 获取所有的数据表
     * /index/tables.
     *
     * @return \think\Response
     */
    public function tables()
    {
        $list = Backup::tables();

        return $this->success($list);
    }

    /**
     * 获取所有备份文件
     * /index/filelist.
     *
     * @return \think\Response
     */
    public function filelist()
    {
        $list = Backup::files();

        return $this->success($list, '拉去本地文件成功');
    }

    /**
     * 导入
     * /index/import?file=fastadmin-mysql-20240416184903.sql.
     *
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

    /**
     * 导出
     * 1. 提交备份任务：/index/export发送post请求，数据格式`{ "tables": ["admin","log"]}` 响应`['index' => 0, 'page' => 1]`
     * 2. 发送备份数据请求：/index/export发送get请求/index/export?index=0&page=0,直到page=0表示该数据备份完成
     * 3. 备份完成：/index/cleanup 发送请求，全部备份已经完成,主要作用还是清理在备份中生成的缓存，不清理可能会对下一次任务有影响.
     *
     * @return \think\Response|void
     */
    public function export()
    {
        $validate = new ExportValidate();
        if (request()->isPost()) {
            $data = request()->post();

            if ( ! $validate->scene("step1")->check($data)) {
                return $this->error($validate->getError());
            }
            try {
                if (Backup::backupStep1($data["tables"])) {
                    return $this->success(['index' => 0, 'page' => 1], '初始化成功！');
                }
            } catch (LockException $exception) {
                return $this->error('检测到有一个备份任务正在执行，请稍后再试！');
            } catch (\Exception $exception) {
                return $this->error($exception->getMessage());
            }
        } elseif (request()->isGet()) {
            $data = request()->get();
            if ( ! $validate->scene("step2")->check($data)) {
                return $this->error($validate->getError());
            }
            $index = (int) $data["index"];
            $lastPage = Backup::backupStep2($index, $data["page"]);
            if ($lastPage == 0) {
                return $this->success(['index' => $index + 1, 'page' => $lastPage], '备份完毕！');
            } else {
                return $this->success(['index' => $index, 'page' => $lastPage], '需要继续进行备份数据！');
            }
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
        if (Backup::repair($tables)) {
            return $this->success("数据表修复完成！");
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
        if (Backup::optimize($tables)) {
            return $this->success("数据表优化完成！");
        } else {
            return $this->error("数据表优化出错请重试！");
        }
    }

}
