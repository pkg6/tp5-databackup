<?php

namespace tp5er\Backup;

use InvalidArgumentException;
use think\App;
use tp5er\Backup\reader\Mysql;
use tp5er\Backup\writer\SqlFileWriter;

class DbBackup
{
    protected array $config;
    protected string $connectionName;
    protected string $database;
    public Mysql $reader;
    public SqlFileWriter $writer;

    public function __construct(App $app)
    {
        $cfg = $app->config->get('backup', []);
        $this->config = array_merge(['limit' => 100, 'drop_sql' => false, 'before_import_sql' => []], $cfg);
        $this->connect($app, $app->config->get('database.default'));
    }

    protected function connect(App $app, string $name): void
    {
        $connections = $app->config->get('database.connections');
        if (!isset($connections[$name])) {
            throw new InvalidArgumentException("Undefined db config: {$name}");
        }
        $this->connectionName = $name;
        $this->database = $connections[$name]['database'] ?? '';
        $this->reader = new Mysql($app->get('db')->connect($name));
        $this->writer = new SqlFileWriter($this->config['path'] ?? $app->getRootPath() . 'backup');
    }

    public function database(?string $name = null): self
    {
        if ($name !== null && $name !== $this->connectionName) {
            $this->connect(app(), $name);
        }
        return $this;
    }

    public function tables(): array { return $this->reader->tables(); }
    public function repair($t) { return $this->reader->repair($t); }
    public function optimize($t = null) { return $this->reader->optimize($t); }
    public function files(): array { return $this->writer->files(); }
    public function drop($t) { return $this->reader->drop($t); }
    public function truncate($t) { return $this->reader->truncate($t); }
    public function prefixChange($tables, $prefix) { return $this->reader->prefixChange($tables, $prefix); }

    public function backup(array $tables): bool
    {
        $file = $this->writer->generateFilename($this->database, $this->connectionName);
        $this->writer->write($file, $this->reader->header($this->database, $this->connectionName));

        $limit = $this->config['limit'];
        foreach ($tables as $table) {
            [$sql, $hasData] = $this->reader->tableStructure($table, $this->config['drop_sql']);
            $this->writer->write($file, $sql);
            
            // 添加 AUTO_INCREMENT 语句（在 continue 之前）
            $autoIncrement = $this->reader->getAutoIncrement($table);
            if ($autoIncrement > 0) {
                $autoIncrementSql = "-- -----------------------------\n";
                $autoIncrementSql .= "-- AUTO_INCREMENT for table `{$table}`\n";
                $autoIncrementSql .= "-- -----------------------------\n";
                $autoIncrementSql .= "ALTER TABLE `{$table}` MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT={$autoIncrement};\n\n";
                $this->writer->write($file, $autoIncrementSql);
            }
            
            if (!$hasData) continue;
            $offset = 0;
            while ($sql = $this->reader->tableData($table, $limit, $offset)) {
                $this->writer->write($file, $sql);
                $offset += $limit;
            }
        }
        
        // 备份完成后自动压缩为 gz 格式
        $this->compressBackup($file);
        
        return true;
    }

    public function export(array $tables): array
    {
        $file = $this->writer->generateFilename($this->database, $this->connectionName);
        $this->writer->write($file, $this->reader->header($this->database, $this->connectionName));

        $limit = $this->config['limit'];
        $result = ['file' => $file, 'count' => 0, 'steps' => 0, 'list' => []];

        foreach ($tables as $i => $table) {
            $c = $this->reader->tableCount($table);
            $s = (int) ceil($c / $limit) + 1;
            $result['count'] += $c;
            $result['steps'] += $s;
            $result['list'][$i] = compact('table') + ['count' => $c, 'limit' => $limit, 'steps' => $s];
        }

        return $result;
    }

    public function exportStep(string $file, string $table, int $page): int
    {
        $limit = $this->config['limit'];
        if ($page === 1) {
            [$sql] = $this->reader->tableStructure($table, $this->config['drop_sql']);
            $this->writer->write($file, $sql);
        }
        $sql = $this->reader->tableData($table, $limit, ($page - 1) * $limit);
        if ($sql === '') {
            // 表备份完成，添加 AUTO_INCREMENT 语句
            $autoIncrement = $this->reader->getAutoIncrement($table);
            if ($autoIncrement > 0) {
                $autoIncrementSql = "-- -----------------------------\n";
                $autoIncrementSql .= "-- AUTO_INCREMENT for table `{$table}`\n";
                $autoIncrementSql .= "-- -----------------------------\n";
                $autoIncrementSql .= "ALTER TABLE `{$table}` MODIFY `id` int unsigned NOT NULL AUTO_INCREMENT,AUTO_INCREMENT={$autoIncrement};\n\n";
                $this->writer->write($file, $autoIncrementSql);
            }
            return 0;
        }
        $this->writer->write($file, $sql);
        return $page + 1;
    }

    public function import(string $file): bool
    {
        $sqls = $this->config['before_import_sql'];
        
        // 解析文件路径
        $filePath = $this->writer->resolve($file);
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        
        // 记录原始文件路径（用于删除）
        $originalFile = $filePath;
        
        // 如果是压缩文件，先解压
        if ($extension === 'gz' || $extension === '7z') {
            $filePath = $this->extractFile($filePath);
        }
        
        // 读取SQL内容
        $sqls[] = $this->writer->read(basename($filePath));
        
        // 执行导入
        $result = $this->reader->import($sqls);
        
        // 如果是压缩文件，只删除解压后的临时SQL文件（保留原压缩文件）
        if ($extension === 'gz' || $extension === '7z') {
            @unlink($filePath); // 删除解压后的临时SQL文件
        }
        
        return $result;
    }
    
    /**
     * 解压文件
     * @param string $filePath 压缩文件路径
     * @return string 解压后的SQL文件路径
     * @throws \Exception
     */
    protected function extractFile(string $filePath): string
    {
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $dir = dirname($filePath);
        $baseName = basename($filePath, '.' . $extension);
        $sqlFilePath = $dir . DIRECTORY_SEPARATOR . $baseName . '.sql';
        
        switch ($extension) {
            case 'gz':
                $this->extractGzFile($filePath, $sqlFilePath);
                break;
            case '7z':
                $this->extract7zFile($filePath, $dir);
                break;
            default:
                throw new \Exception("不支持的压缩格式: {$extension}");
        }
        
        // 检查解压后的文件是否存在
        if (!file_exists($sqlFilePath)) {
            // 尝试查找解压后的SQL文件（可能在子目录中）
            $possiblePaths = [
                $sqlFilePath,
                $dir . DIRECTORY_SEPARATOR . $baseName . DIRECTORY_SEPARATOR . $baseName . '.sql',
                $dir . DIRECTORY_SEPARATOR . 'backup.sql',
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists($path)) {
                    $sqlFilePath = $path;
                    break;
                }
            }
            
            if (!file_exists($sqlFilePath)) {
                throw new \Exception("解压后未找到SQL文件");
            }
        }
        
        return $sqlFilePath;
    }
    
    /**
     * 解压 gz 文件
     * @param string $gzFilePath gz文件路径
     * @param string $outputPath 输出路径
     */
    protected function extractGzFile(string $gzFilePath, string $outputPath): void
    {
        $gzFile = gzopen($gzFilePath, 'rb');
        if (!$gzFile) {
            throw new \Exception("无法打开GZ文件: {$gzFilePath}");
        }
        
        $outputFile = fopen($outputPath, 'wb');
        if (!$outputFile) {
            gzclose($gzFile);
            throw new \Exception("无法创建输出文件: {$outputPath}");
        }
        
        while (!gzeof($gzFile)) {
            fwrite($outputFile, gzread($gzFile, 4096));
        }
        
        gzclose($gzFile);
        fclose($outputFile);
    }
    
    /**
     * 解压 7z 文件
     * @param string $sevenZFilePath 7z文件路径
     * @param string $outputDir 输出目录
     */
    protected function extract7zFile(string $sevenZFilePath, string $outputDir): void
    {
        // 尝试使用 7z 命令行工具
        $command = "7z x \"{$sevenZFilePath}\" -o\"{$outputDir}\" -y";
        
        // Windows 系统
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $output = [];
            $returnVar = 0;
            exec($command, $output, $returnVar);
            
            if ($returnVar !== 0) {
                throw new \Exception("7z解压失败: " . implode("\n", $output));
            }
        } else {
            // Linux/Unix 系统
            $output = shell_exec($command);
            if ($output === null) {
                throw new \Exception("7z解压失败");
            }
        }
    }
    
    /**
     * 压缩备份文件为 gz 格式
     * @param string $sqlFileName SQL文件名
     */
    public function compressBackup(string $sqlFileName): void
    {
        $sqlFilePath = $this->writer->resolve($sqlFileName);
        $gzFilePath = $sqlFilePath . '.gz';
        
        // 打开SQL文件
        $sqlFile = fopen($sqlFilePath, 'rb');
        if (!$sqlFile) {
            return; // 文件不存在，跳过压缩
        }
        
        // 创建GZ文件
        $gzFile = gzopen($gzFilePath, 'wb');
        if (!$gzFile) {
            fclose($sqlFile);
            return;
        }
        
        // 逐块读取并写入
        while (!feof($sqlFile)) {
            gzwrite($gzFile, fread($sqlFile, 4096));
        }
        
        // 关闭文件
        gzclose($gzFile);
        fclose($sqlFile);
        
        // 删除原SQL文件，只保留压缩文件
        @unlink($sqlFilePath);
    }
}
