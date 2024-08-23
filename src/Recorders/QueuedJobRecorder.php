<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Queue\Events\JobFailed;

class QueuedJobRecorder extends Recorder
{
    public static $events = [
        JobFailed::class
    ];

    /**
     * @param $event
     * @return void
     */
    public function trackEvent($event)
    {
        if (!$this->shouldReportJob($event->job)) {
            return;
        }

        $this->laritor->pushEvent('failed_jobs', [
            'connection' => $event->connectionName,
            'queue' => $event->job->queue ?? config("queue.connections.{$event->connectionName}.queue", 'default'),
            'job' => $event->job->payload()['displayName'] ?? get_class($event->job),
            'exception' => $event->exception->getMessage()
        ]);
    }

    public function shouldReportJob($job)
    {
        foreach ((array)config('laritor.jobs.ignore') as $ignore ) {

            if ($job instanceof $ignore ) {
                return false;
            }
        }

        return true;
    }
}
