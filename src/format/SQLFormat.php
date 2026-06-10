<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * (L) Licensed <https://opensource.org/license/MIT>
 *
 * (A) zhiqiang <https://www.zhiqiang.wang>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\format;

use think\helper\Arr;
use tp5er\Backup\BackupInterface;

class SQLFormat
{
    public static function dividingLine()
    {
        return PHP_EOL . '-- -----------------------------' . PHP_EOL;
    }

    /**
     * @param BackupInterface $backup
     *
     * @return string
     */
    public static function copyright(BackupInterface $backup)
    {
        $config = $backup->getDatabaseConfig();
        $hostname = Arr::get($config, "hostname");
        $hostport = Arr::get($config, "hostport");
        $sql = self::dividingLine();
        $sql .= "-- tp5-databackup SQL Dump " . PHP_EOL;
        $sql .= "-- version " . databackup_version() . PHP_EOL;
        $sql .= "-- https://github.com/pkg6/tp5-databackup " . PHP_EOL;
        $sql .= "-- " . PHP_EOL;
        $sql .= "-- Host     : " . $hostname . PHP_EOL;
        $sql .= "-- Port     : " . $hostport . PHP_EOL;
        $sql .= "-- Database : " . $backup->getDatabase() . PHP_EOL;
        $sql .= "-- PHP Version : " . phpversion() . PHP_EOL;
        $sql .= "-- Date : " . date("Y-m-d H:i:s");
        $sql .= self::dividingLine();
        $sql .= 'SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";' . PHP_EOL;
        $sql .= 'SET FOREIGN_KEY_CHECKS = 0;' . PHP_EOL;

        return $sql;
    }

    /**
     * @param $table
     * @param $createTableSQL
     * @param bool $withDrop
     *
     * @return string
     */
    public static function tableStructure($table, $createTableSQL, $withDrop = false)
    {
        $sql = self::dividingLine();
        $sql .= "-- Table structure for $table";
        $sql .= self::dividingLine();
        $sql .= PHP_EOL;
        if ($withDrop) {
            $sql .= "DROP TABLE IF EXISTS `{$table}`; " . PHP_EOL;
        }
        $sql .= $createTableSQL;

        return $sql;
    }

    /**
     * @param $result
     *
     * @return string
     */
    public static function executeTableStructure($result)
    {
        $sql = trim($result[0]['Create Table'] ?? $result[0]['Create View']);
        $sql .= ";" . PHP_EOL;

        return $sql;
    }

    /**
     * @param $table
     * @param $instertSQL
     * @param $annotation
     *
     * @return string
     */
    public static function tableData($table, $instertSQL, $annotation)
    {
        $sql = "";
        if ($annotation) {
            $sql .= self::dividingLine();
            $sql .= "-- Records of $table";
            $sql .= self::dividingLine();
        }
        //INSERT INTO 开始事务的方式
        //$sql .= "BEGIN;";
        $sql .= PHP_EOL . $instertSQL . ';';

        return $sql;
    }

    /**
     * @param $table
     * @param $result
     *
     * @return string
     */
    public static function tableInsert($table, $result)
    {
        if (count($result) <= 0) {
            return "";
        }
        $tableFieldArr = self::tableField($result[0]);
        $sql = "INSERT INTO `{$table}` (" . implode(",", $tableFieldArr) . ") VALUES ";
        $tableDataArr = [];
        foreach ($result as &$row) {
            foreach ($row as &$val) {
                if (is_numeric($val)) {
                } elseif (is_null($val)) {
                    $val = 'NULL';
                } else {
                    $val = "'" . str_replace(["\r", "\n"], ['\\r', '\\n'], addslashes($val)) . "'";
                }
            }
            $tableDataArr[] = PHP_EOL . "(" . implode(", ", array_values($row)) . ")";
        }
        $sql .= implode(",", $tableDataArr);

        return $sql;
    }

    /**
     * @param array $field
     *
     * @return array
     */
    protected static function tableField(array $field)
    {
        $sqlArr = [];
        foreach ($field as $f => $v) {
            $sqlArr[$f] = "`{$f}`";
        }

        return $sqlArr;
    }
}
