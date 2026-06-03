<?php

namespace tp5er\Backup\format;

class SQLFormat
{
    public static function header(string $database, string $connectionName): string
    {
        $sql = PHP_EOL . '-- -----------------------------' . PHP_EOL;
        $sql .= '-- tp5-databackup SQL Dump' . PHP_EOL;
        $sql .= '-- Database: ' . $database . PHP_EOL;
        $sql .= '-- Connection: ' . $connectionName . PHP_EOL;
        $sql .= '-- Date: ' . date('Y-m-d H:i:s') . PHP_EOL;
        $sql .= '-- -----------------------------' . PHP_EOL;
        $sql .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL;
        $sql .= 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL;

        return $sql;
    }

    public static function tableStructure(string $table, string $createSql, bool $withDrop = false): string
    {
        $sql = PHP_EOL . '-- -----------------------------' . PHP_EOL;
        $sql .= '-- Table structure for ' . $table . PHP_EOL;
        $sql .= '-- -----------------------------' . PHP_EOL . PHP_EOL;
        if ($withDrop) {
            $sql .= "DROP TABLE IF EXISTS `{$table}`;" . PHP_EOL;
        }
        $sql .= $createSql . ';' . PHP_EOL;

        return $sql;
    }

    public static function tableInsert(string $table, array $rows): string
    {
        if (empty($rows)) {
            return '';
        }

        $columns = [];
        foreach ($rows[0] as $field => $_) {
            $columns[] = "`{$field}`";
        }

        $sql = "INSERT INTO `{$table}` (" . implode(',', $columns) . ') VALUES';
        $values = [];
        foreach ($rows as $row) {
            $vals = [];
            foreach ($row as $val) {
                if (is_numeric($val)) {
                    $vals[] = $val;
                } elseif (is_null($val)) {
                    $vals[] = 'NULL';
                } else {
                    $vals[] = "'" . str_replace(["\r", "\n"], ['\\r', '\\n'], addslashes($val)) . "'";
                }
            }
            $values[] = PHP_EOL . '(' . implode(', ', $vals) . ')';
        }
        $sql .= implode(',', $values) . ';';

        return $sql;
    }
}
