<?php

/*
 * This file is part of the tp5er/tp5-databackup.
 *
 * (c) pkg6 <https://github.com/pkg6>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace tp5er\Backup\task;

use think\queue\Job as tpJob;

/**
 * Class Job.
 */
class Job
{
    /**
     * @param tpJob $job
     * @param $data
     *
     * @return void
     */
    public function fire(tpJob $job, $data)
    {
        try {
            //执行任务
            $isJobDone = backup_run($data);
            if ($isJobDone) {
                // 如果任务执行成功， 记得删除任务
                $job->delete();
            } else {
                $this->attemptsErr($job, $data);
            }
        } catch (\Exception $exception) {
            $this->attemptsErr($job, $data);
        }
    }

    public function attemptsErr(tpJob $job, $data)
    {
        app()->log->warning("tp5er.backup 任务队列正在进行重试操作", $data);
        if ($job->attempts() > 3) {
            app()->log->error("tp5er.backup 任务执行失败,删除队列", $data);
            $job->delete();
        }
    }
}
