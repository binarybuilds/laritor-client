<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;

class QueuedJobRecorder extends Recorder
{
    /**
     * @var string
     */
    public static $eventType = 'jobs';

    public static $events = [
        JobQueued::class,
        JobProcessing::class,
        JobFailed::class,
        JobProcessed::class
    ];

    /**
     * @param JobFailed|JobProcessing|JobProcessed $event
     * @return void
     */
    public function trackEvent($event)
    {
        if (!$this->shouldReportJob($event->job)) {
            return;
        }

        if ($event instanceof JobQueued ) {
            $this->queued($event);
        }
        elseif ($event instanceof JobProcessing ) {
            $this->processing($event);
        } elseif ($event instanceof JobFailed ) {
            $this->complete($event);
        } elseif ($event instanceof JobProcessed ) {
            $this->complete($event);
        }
    }

    /**
     * @param JobQueued $event
     * @return void
     */
    public function queued(JobQueued $event)
    {
        $this->laritor->pushEvent(static::$eventType, [
            'connection' => $event->connectionName,
            'queue' => $event->job->queue ?? config("queue.connections.{$event->connectionName}.queue", 'default'),
            'job' =>  isset($event->payload()['displayName']) ? $event->payload()['displayName'] : get_class($event->job),
            'id' => $event->id,
            'delay' => $event->delay,
            'queued_at' => now()->format('Y-m-d H:i:s'),
            'status' => 'queued',
            'context' => $this->laritor->getContext()
        ]);
    }

    public function processing(JobProcessing $event)
    {
        $this->laritor->setContext('JOB');
        $this->laritor->pushEvent(static::$eventType, [
            'connection' => $event->connectionName,
            'queue' => $event->job->queue ?? config("queue.connections.{$event->connectionName}.queue", 'default'),
            'job' =>  isset($event->job->payload()['displayName']) ? $event->job->payload()['displayName'] : get_class($event->job),
            'started_at' => now(),
            'id' => $event->job->getJobId()
        ]);
    }

    /**
     * @param JobFailed|JobProcessed $event
     * @return void
     */
    public function complete($event)
    {
        $job = $this->laritor->getEvents(static::$eventType)[0];
        $job['duration'] = $job['started_at']->diffInMilliseconds();
        $job['started_at'] = $job['started_at']->toDateTimeString();
        $job['completed_at'] = now()->toDateTimeString();
        $job['id'] = $event->job->getJobId();
        $job['status'] = $event instanceof JobFailed ? 'failed' : 'processed';

        $this->laritor->addEvents('jobs', [$job]);

        $this->laritor->sendEvents();
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
