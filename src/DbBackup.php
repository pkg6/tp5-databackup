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

    public function backup(array $tables): bool
    {
        $file = $this->writer->generateFilename($this->database, $this->connectionName);
        $this->writer->write($file, $this->reader->header($this->database, $this->connectionName));

        $limit = $this->config['limit'];
        foreach ($tables as $table) {
            [$sql, $hasData] = $this->reader->tableStructure($table, $this->config['drop_sql']);
            $this->writer->write($file, $sql);
            if (!$hasData) continue;
            $offset = 0;
            while ($sql = $this->reader->tableData($table, $limit, $offset)) {
                $this->writer->write($file, $sql);
                $offset += $limit;
            }
        }
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
        if ($sql === '') return 0;
        $this->writer->write($file, $sql);
        return $page + 1;
    }

    public function import(string $file): bool
    {
        $sqls = $this->config['before_import_sql'];
        $sqls[] = $this->writer->read($file);
        return $this->reader->import($sqls);
    }
}
