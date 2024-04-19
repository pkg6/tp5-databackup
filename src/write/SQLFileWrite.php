<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\write;

use tp5er\Backup\exception\FileNotException;

class SQLFileWrite extends WriteAbstract
{
    /**
     *
     */
    public const ext = "sql";

    /**
     * @param string $sql
     *
     * @return bool
     */
    public function writeSQL(string $sql)
    {
        $result = file_put_contents(
            $this->getFileName(),
            $sql . PHP_EOL,
            LOCK_EX | FILE_APPEND
        );
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
     *
     * @return array|false|string|string[]
     */
    public function readSQL($file)
    {
        if ( ! file_exists($file)) {
            throw new FileNotException($file);
        }
        $sql = file_get_contents($file);
        $sqlArr = explode(PHP_EOL . PHP_EOL, $sql);

        return $sqlArr;
    }
}
