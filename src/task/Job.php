<?php

namespace tp5er\Backup\task;

use think\queue\Job as tpJob;

class Job
{
    public function fire(tpJob $job, $data)
    {
        try {
            $isJobDone = backup_run($data);
            if ($isJobDone) {
                $job->delete();
            } else {
                $this->attemptsErr($job, $data);
            }
        } catch (\Exception $e) {
            $this->attemptsErr($job, $data);
        }
    }

    public function attemptsErr(tpJob $job, $data)
    {
        app()->log->warning('tp5er.backup 任务队列正在进行重试操作', $data);
        if ($job->attempts() > 3) {
            app()->log->error('tp5er.backup 任务执行失败,删除队列', $data);
            $job->delete();
        }
    }
}
