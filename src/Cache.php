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

namespace tp5er\Backup;

use think\App;

final class Cache
{
    const Tag = "tp5er.backup";
    const LockPrefix = "tp5er.backup.lock.";
    const File = "tp5er.backup.file";
    const Tables = "tp5er.backup.tables";
    const TableCounts = "tp5er.backup.tables.counts";
    const Table = "tp5er.backup.table.";

    public static function set(App $app, $key, $value, $ttl = null)
    {
        return $app->cache->tag(Cache::Tag)->set($key, $value, $ttl);
    }

    public static function clear(App $app)
    {
        return $app->cache->tag(Cache::Tag)->clear();
    }
}
