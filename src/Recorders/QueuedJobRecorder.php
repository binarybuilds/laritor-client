<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Laritor\LaravelClient\Laritor;

class QueuedJobRecorder extends Recorder
{
    public static $events = [
        JobFailed::class,
        JobProcessed::class
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

        $this->laritor->pushEvent('jobs', [
            'connection' => $event->connectionName,
            'queue' => $event->job->queue ?? config("queue.connections.{$event->connectionName}.queue", 'default'),
            'job' => $event->job->payload()['displayName'] ?? get_class($event->job),
            'exception' => $event instanceof JobFailed ? $event->exception->getMessage() : '',
            'status' =>  $event instanceof JobFailed ? 'failed' : 'completed'
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

    /**
     * @param Laritor $laritor
     * @return bool
     */
    public static function shouldReportEvents( Laritor $laritor )
    {
        return collect( $laritor->getEvents('jobs'))
            ->where('status', 'failed')
            ->isNotEmpty();
    }
}
