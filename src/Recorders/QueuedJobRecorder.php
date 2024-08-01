<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Queue\Events\JobFailed;

class QueuedJobRecorder extends Recorder
{
    /**
     * @param JobFailed $event
     * @return void
     */
    public function trackEvent($event)
    {
        if ($this->shouldReportJob($event->job)) {
            $data = [
                'type' => 'failed_job',
                'connection' => $event->connectionName,
                'queue' => $event->job->queue ?? config("queue.connections.{$event->connectionName}.queue", 'default'),
                'job' => $event->job->payload()['displayName'] ?? get_class($event->job),
                'exception' => $event->exception->getMessage(),
            ];

            $this->laritor->addEvent($data);
        }
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
