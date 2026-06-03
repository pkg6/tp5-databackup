<?php

namespace tp5er\Backup\writer;

use FilesystemIterator;
use tp5er\Backup\exception\BackupException;

class SqlFileWriter
{
    protected string $path;

    public function __construct(string $path)
    {
        $this->ensureDirectory($path);
        $this->path = $path;
    }

    public function generateFilename(string $database, string $connectionName): string
    {
        return sprintf('%s-%s-%s.sql', $database, $connectionName, date('YmdHis'));
    }

    public function write(string $filename, string $sql): void
    {
        $bytes = file_put_contents($this->resolve($filename), $sql . PHP_EOL, FILE_APPEND | LOCK_EX);
        if ($bytes === false) {
            throw new BackupException("Write failed: {$filename}");
        }
        clearstatcache();
    }

    public function read(string $filename): string
    {
        $path = $this->resolve($filename);
        if (!file_exists($path)) {
            throw new BackupException("File not found: {$path}");
        }

        return file_get_contents($path);
    }

    public function files(): array
    {
        $list = [];
        foreach (new FilesystemIterator($this->path, FilesystemIterator::KEY_AS_FILENAME) as $file) {
            if ($file->isFile() && $file->getExtension() === 'sql') {
                $name = $file->getFilename();
                $parts = explode('-', str_replace('.sql', '', $name));
                $list[] = [
                    'name' => $name,
                    'filename' => $file->getPathname(),
                    'database' => $parts[0] ?? '',
                    'connection' => $parts[1] ?? '',
                    'size' => format_bytes($file->getSize()),
                    'time' => date('Y-m-d H:i:s', $file->getMTime()),
                ];
            }
        }

        return $list;
    }

    protected function resolve(string $filename): string
    {
        if (strpos($filename, DIRECTORY_SEPARATOR) === false && strpos($filename, '/') === false) {
            return $this->path . DIRECTORY_SEPARATOR . $filename;
        }

        return $filename;
    }

    protected function ensureDirectory(string $path): void
    {
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }
    }
}
