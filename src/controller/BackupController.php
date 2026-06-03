<?php

namespace tp5er\Backup\controller;

use think\facade\View;
use think\helper\Str;
use think\Validate;
use tp5er\Backup\DbBackup as BackupService;
use tp5er\Backup\facade\Backup;

class BackupController
{
    protected function db(): BackupService
    {
        return Backup::database();
    }

    protected function fetch(string $name, ?string $prefix = null): string
    {
        $prefix = $prefix ?: config('backup.route_prefix', 'backup');

        View::config([
            'view_path' => __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR,
        ]);

        $routes = [
            'tables' => $prefix . '/tables',
            'optimize' => $prefix . '/optimize',
            'repair' => $prefix . '/repair',
            'backupStep1' => $prefix . '/backupStep1',
            'backupStep2' => $prefix . '/backupStep2',
            'cleanup' => $prefix . '/cleanup',
            'files' => $prefix . '/files',
            'import' => $prefix . '/doImport',
            'download' => $prefix . '/download',
            'delete' => $prefix . '/delete',
            'view_backup' => $prefix . '/index',
            'view_import' => $prefix . '/import',
            "drop" => $prefix . "/drop",
            "truncate" => $prefix . "/truncate",
            "prefixChange" => $prefix . "/prefixChange",
        ];

        $layuiConfig = config('backup.layui', []);
        View::assign('routes', $routes);
        View::assign('layui', [
            'layuijs' => $layuiConfig['layuijs'] ?? '//unpkg.com/layui@2.9.8/dist/layui.js',
            'layuicss' => $layuiConfig['layuicss'] ?? '//cdn.staticfile.org/layui/2.9.7/css/layui.css',
        ]);

        return View::fetch($name);
    }

    public function index(?string $prefix = null): string
    {
        return $this->fetch('backup/backup', $prefix);
    }

    public function import(?string $prefix = null): string
    {
        return $this->fetch('backup/import', $prefix);
    }

    public function tables()
    {
        $list = $this->db()->tables();
        $ret = [];
        foreach ($list as $k => $item) {
            foreach ($item as $field => $value) {
                $f = Str::snake($field);
                if ($f === 'data_length') {
                    $value = format_bytes($value);
                }
                $ret[$k][$f] = $value;
            }
        }

        return backup_success($ret);
    }

    public function files()
    {
        return backup_success($this->db()->files(), '获取备份文件成功');
    }

    public function doImport()
    {
        $file = request()->param('name');
        try {
            $this->db()->import($file);

            return backup_success([], '数据还原成功');
        } catch (\Exception $e) {
            return backup_error($e->getMessage());
        }
    }

    public function backupStep1()
    {
        $data = request()->post();
        $validate = new Validate();
        $validate->rule('tables', 'require')->message(['tables.require' => '表数据不能为空']);
        if (!$validate->check($data)) {
            return backup_error($validate->getError());
        }

        try {
            $result = $this->db()->export($data['tables']);

            return backup_success([
                'index' => 0,
                'page' => 1,
                'file' => $result['file'],
                'tables' => $result['list'],
                'steps' => $result['steps'],
                'count' => $result['count'],
            ], '初始化成功！');
        } catch (\Exception $e) {
            return backup_error($e->getMessage());
        }
    }

    public function backupStep2()
    {
        $data = request()->get();
        $validate = new Validate();
        $validate
            ->rule('file', 'require')
            ->rule('table', 'require')
            ->rule('page', 'require|number')
            ->message([
                'file.require' => '文件名不能为空',
                'table.require' => '表名不能为空',
                'page.require' => '分页不能为空',
                'page.number' => '分页必须为数字',
            ]);
        if (!$validate->check($data)) {
            return backup_error($validate->getError());
        }

        $lastPage = $this->db()->exportStep($data['file'], $data['table'], (int) $data['page']);

        if ($lastPage === 0) {
            return backup_success([
                'index' => $data['index'] ?? 0,
                'page' => 0,
                'table' => $data['table'],
            ], '单表备份完毕！');
        }

        return backup_success([
            'index' => $data['index'] ?? 0,
            'page' => $lastPage,
            'table' => $data['table'],
        ], '继续备份');
    }

    public function cleanup()
    {
        $file = request()->param('file');
        
        // 如果提供了文件名，进行压缩
        if (!empty($file)) {
            try {
                $this->db()->compressBackup($file);
                return backup_success([], '备份完成，正在压缩...');
            } catch (\Exception $e) {
                // 压缩失败不影响备份结果，继续返回成功
                return backup_success([], '备份完成（压缩失败：' . $e->getMessage() . '）');
            }
        }
        
        return backup_success([], '整库备份完毕！');
    }

    public function repair()
    {
        $tables = request()->post('tables');
        if (is_null($tables)) {
            return backup_error('没有获取到表');
        }
        $this->db()->repair($tables);

        return backup_success($tables, '数据表修复完成！');
    }

    public function optimize()
    {
        $tables = request()->post('tables');
        if (is_null($tables)) {
            return backup_error('没有获取到表');
        }
        $this->db()->optimize($tables);

        return backup_success($tables, '数据表优化完成！');
    }

    public function download()
    {
        $filename = request()->param('filename');

        return backup_download($filename);
    }

    public function delete()
    {
        $filename = request()->param('filename');
        unlink($filename);

        return backup_success('', '删除成功');
    }

    
    /**
     * @summary 删除表
     *
     * @param array|string tables
     *
     * @return \think\Response
     */
    public function drop()
    {
        $tables = request()->param("tables");
        if (is_null($tables)) {
            return backup_error("没有获取到表");
        }
        if ($this->db()->drop($tables)) {
            return backup_success($tables, "数据表删除完成！");
        } else {
            return backup_error("数据表删除出错请重试");
        }
    }

    /**
     * @summary 清空表
     *
     * @param array|string tables
     *
     * @return \think\Response
     */
    public function truncate()
    {
        $tables = request()->param("tables");
        if (is_null($tables)) {
            return backup_error("没有获取到表");
        }
        if ($this->db()->truncate($tables)) {
            return backup_success($tables, "数据表清空完成！");
        } else {
            return backup_error("数据表清空出错请重试");
        }
    }

    /**
     * @summary 批量修改表前缀
     *
     * @description 批量修改选中表的前缀，例如将表前缀从 old_ 修改为 new_
     *
     * @param array|string tables 要修改前缀的表名列表
     * @param string prefix 新的表前缀
     *
     * @return \think\Response
     */
    public function prefixChange()
    {
        $tables = request()->param("tables");
        $prefix = request()->param("prefix");
        
        if (is_null($tables)) {
            return backup_error("没有获取到表");
        }
        if (is_null($prefix) || trim($prefix) === '') {
            return backup_error("请输入表前缀");
        }
        
        // 调用底层的 prefixChange 方法
        $successCount = $this->db()->prefixChange($tables, $prefix);
        
        if ($successCount > 0) {
            return backup_success([], "成功修改 {$successCount} 个表的前缀！");
        } else {
            return backup_error("修改表前缀失败，请检查后重试！");
        }
    }

}
