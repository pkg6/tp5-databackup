<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

if ( ! function_exists('format_bytes')) {
    /**
     * 格式化字节大小.
     *
     * @param number $size 字节数
     * @param string $delimiter 数字和单位分隔符
     *
     * @return string            格式化后的带单位的大小
     */
    function format_bytes($size, $delimiter = '')
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        for ($i = 0; $size >= 1024 && $i < 5; $i++) {
            $size /= 1024;
        }

        return round($size, 2) . $delimiter . $units[$i];
    }
}
