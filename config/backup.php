<?php

return [
    'path' => runtime_path() . 'backup' . DIRECTORY_SEPARATOR,
    'limit' => 100,
    'drop_sql' => true,
    'before_import_sql' => [],
    'route_prefix' => 'backup',
];
