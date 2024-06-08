<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;


class QueuedJobRecorder extends Recorder
{
    /**
     * @param $event
     * @return void
     */
    public function trackEvent($event)
    {
        if ($event instanceof JobQueued ) {
            $this->queued($event);
        } elseif ($event instanceof JobProcessing ) {
            $this->started($event);
        } elseif ($event instanceof JobProcessed ) {
            $this->processed($event);
        } elseif ($event instanceof JobFailed ) {
            $this->failed($event);
        }
    }

    /**
     * @param JobQueued $event
     * @return void
     */
    public function queued(JobQueued $event)
    {
        $data = [
            'type' => 'job',
            'start_at' => $this->getStartAt($event->job->delay),
            'connection' => $event->connectionName,
            'queue' => $event->job->queue ?? config("queue.connections.{$event->connectionName}.queue", 'default'),
            'job' => get_class($event->job),
            'status' => 'queued'
        ];

        $this->laritor->addEvent($data);
    }


    /**
     * @param JobProcessing $event
     * @return void
     */
    public function started(JobProcessing $event)
    {
        $data = [
            'type' => 'job',
            'started_at' => now(),
            'connection' => $event->connectionName,
            'queue' => $event->job->queue ?? config("queue.connections.{$event->connectionName}.queue", 'default'),
            'job' => $event->job->payload()['displayName'] ?? get_class($event->job),
            'status' => 'started'
        ];

        $this->laritor->addEvent($data);
    }

    /**
     * @param JobProcessed $event
     * @return void
     */
    public function processed(JobProcessed $event)
    {
        $this->laritor->completeJob($event);
    }

    /**
     * @param JobFailed $event
     * @return void
     */
    public function failed(JobFailed $event)
    {
        $this->laritor->completeJob($event);
    }

    /**
     * @param $time
     * @return string
     */
    private function getStartAt($time)
    {
        $timestamp = now();

        if ( is_int($time)) {
            $timestamp = $timestamp->addSeconds($time);
        } elseif($time instanceof \DateTimeInterface) {
            $timestamp = $time;
        }
        
        return $timestamp->toDateTimeString();
    }
}
