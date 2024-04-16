<?php

namespace tp5er\Backup\write;


use tp5er\Backup\exception\FileException;
use tp5er\Backup\WriteAbstract;

class SQLFileWrite extends WriteAbstract
{
    /**
     *
     */
    public const ext = "sql";

    /**
     * @param string $sql
     * @return bool
     */
    public function writeSQL(string $sql)
    {
        $result = file_put_contents($this->getFileName(), $sql . PHP_EOL, LOCK_EX | FILE_APPEND);
        if ($result) {
            clearstatcache();
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    public function ext()
    {
        return SQLFileWrite::ext;
    }

    /**
     * @param $file
     * @return array|false|string|string[]
     */
    public function readSQL($file)
    {
        if (!file_exists($file)) {
            throw new FileException($file);
        }
        $sql    = file_get_contents($file);
        $newsql = "";
        foreach (explode(";\n", trim($sql)) as $query) {
            $newsql .= $query;
        }
        return $newsql;
    }
}