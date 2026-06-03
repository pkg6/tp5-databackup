<?php

namespace tp5er\Backup;

use think\facade\Route as RouteFacade;

class Route
{
    public static function register()
    {
        $prefix = config('backup.route_prefix', 'backup');

        RouteFacade::group($prefix, function () {
            RouteFacade::get('tables', 'BackupController@tables');
            RouteFacade::get('files', 'BackupController@files');
            RouteFacade::get('download', 'BackupController@download');
            RouteFacade::get('delete', 'BackupController@delete');
            RouteFacade::get('doImport', 'BackupController@doImport');
            RouteFacade::post('optimize', 'BackupController@optimize');
            RouteFacade::post('repair', 'BackupController@repair');
            RouteFacade::post('backupStep1', 'BackupController@backupStep1');
            RouteFacade::get('backupStep2', 'BackupController@backupStep2');
            RouteFacade::get('cleanup', 'BackupController@cleanup');
            RouteFacade::get('index', 'BackupController@index');
            RouteFacade::get('import', 'BackupController@import');
            RouteFacade::post('drop', 'BackupController@drop');
            RouteFacade::post('truncate', 'BackupController@truncate');
            RouteFacade::post('prefixChange', 'BackupController@prefixChange');
        })->prefix('\\tp5er\\Backup\\controller\\');
    }
}
