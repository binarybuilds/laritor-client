<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use BinaryBuilds\LaritorClient\Jobs\QueueHealthCheck;
use Carbon\Carbon;
use Illuminate\Queue\Events\JobExceptionOccurred;
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
        JobProcessed::class,
        JobExceptionOccurred::class,
    ];

    /**
     * @param JobFailed|JobProcessing|JobProcessed $event
     * @return void
     */
    public function trackEvent($event)
    {
        if ($event->job instanceof QueueHealthCheck || !FilterHelper::recordQueuedJob($event->job)) {
            return;
        }

        if ($event instanceof JobQueued ) {
            $this->queued($event);
        }
        elseif ($event instanceof JobProcessing ) {
            $this->processing($event);
        } elseif (
            $event instanceof JobFailed ||
            $event instanceof JobExceptionOccurred
        ) {
            app(ExceptionRecorder::class)->handle($event->exception);
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
        $jobPayload = [];

        /** @phpstan-ignore-next-line  */
        if (method_exists($event, 'payload')) {
            $jobPayload = $event->payload();
        }

        $this->laritor->pushEvent(static::$eventType, [
            'connection' => $event->connectionName,
            'queue' => $event->job->queue ?? config("queue.connections.{$event->connectionName}.queue", 'default'),
            'job' =>  isset($jobPayload['displayName']) ? $jobPayload['displayName'] : get_class($event->job),
            'id' => $event->id,
            'delay' => isset($event->delay) ? $event->delay : ( isset($jobPayload['delay']) ? $jobPayload['delay'] : 0 ),
            'queued_at' => now()->toDateTimeString(),
            'status' => 'queued',
            'context' => $this->laritor->getContext(),
            'custom_context' =>DataHelper::getRedactedContext(),
        ]);
    }

    public function processing(JobProcessing $event)
    {
        $this->laritor->setContext('JOB');
        $this->laritor->pushEvent(static::$eventType, [
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue() ?? config("queue.connections.{$event->connectionName}.queue", 'default'),
            'job' =>  isset($event->job->payload()['displayName']) ? $event->job->payload()['displayName'] : get_class($event->job),
            'started_at' => now()->toDateTimeString(),
            'completed_at' => null,
            'duration' => 0,
            'status' => 'processing',
            'id' => $event->job->getJobId()
        ]);
    }

    /**
     * @param JobFailed|JobProcessed $event
     * @return void
     */
    public function complete($event)
    {
        $events = $this->laritor->getEvents(static::$eventType);

        if (!isset($events[0])) {
            return;
        }

        $job = $events[0];
        $start = Carbon::parse($job['started_at']);
        $job['duration'] = $start->diffInMilliseconds();
        $job['started_at'] = $start->toDateTimeString();
        $job['completed_at'] = now()->toDateTimeString();
        $job['id'] = $event->job->getJobId();
        $job['status'] = $event instanceof JobFailed ? 'failed' : 'processed';
        $job['custom_context'] = DataHelper::getRedactedContext();

        $this->laritor->addEvents('jobs', [$job]);

        $this->laritor->sendEvents();
    }
}
