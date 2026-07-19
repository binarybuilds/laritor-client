<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use Carbon\Carbon;
use Illuminate\Queue\Events\JobExceptionOccurred;
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
        JobProcessed::class,
        JobExceptionOccurred::class,
    ];

    /**
     * @param JobExceptionOccurred|JobProcessing|JobProcessed $event
     * @return void
     */
    public function trackEvent($event)
    {
        if ($event instanceof JobQueued ) {
            $this->queued($event);
        }
        elseif ($event instanceof JobProcessing ) {
            $this->processing($event);
        } elseif ($event instanceof JobExceptionOccurred) {
            app(ExceptionRecorder::class)->handle($event->exception);
            $this->laritor->setFailedJob($event->job);
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
            'id' => $this->resolveJobId($event),
            'delay' => isset($event->delay) ? $event->delay : ( isset($jobPayload['delay']) ? $jobPayload['delay'] : 0 ),
            'queued_at' => now()->toDateTimeString(),
            'status' => 'queued',
            'context' => $this->laritor->getContext(),
            'custom_context' => DataHelper::getRedactedContext(),
        ]);
    }

    public function processing(JobProcessing $event)
    {
        if ($event->connectionName !== 'sync') {
            $this->laritor->reset();
        }

        $jobs = [];
        $jobExists = false;
        foreach ($this->laritor->getEvents(static::$eventType) as $job) {
            if (isset($job['id']) && $job['id'] === $this->resolveJobId($event)) {
                $jobExists = true;
                $job['started_at'] = now()->toDateTimeString();
                $job['completed_at'] = null;
                $job['status'] = 'processing';
            }

            $jobs[] = $job;
        }

        $this->laritor->addEvents(static::$eventType, $jobs);

        if (!$jobExists) {
            $job = [
                'connection' => $event->connectionName,
                'queue' => $event->job->getQueue() ?? config("queue.connections.{$event->connectionName}.queue", 'default'),
                'job' =>  isset($event->job->payload()['displayName']) ? $event->job->payload()['displayName'] : get_class($event->job),
                'started_at' => now()->toDateTimeString(),
                'completed_at' => null,
                'duration' => 0,
                'status' => 'processing',
                'id' => $this->resolveJobId($event)
            ];

            if ($event->connectionName === 'sync') {
                $job['queued_at'] = now()->toDateTimeString();
                $job['delay'] = 0;
                $job['context'] = $this->laritor->getContext();
            } else {
                $this->laritor->setContext('JOB');
            }

            $this->laritor->pushEvent(static::$eventType, $job);
        } else {
            $this->laritor->setContext('JOB');
        }
    }

    /**
     * @param JobExceptionOccurred|JobProcessed $event
     * @return void
     */
    public function complete($event)
    {
        $jobs = [];
        foreach ($this->laritor->getEvents(static::$eventType) as $job) {
            if (isset($job['id']) && $job['id'] === $this->resolveJobId($event)) {
                $start = Carbon::parse($job['started_at']);
                $duration = $start->diffInMilliseconds();
                $this->laritor->setJobDuration($duration);
                $job['duration'] = $duration;
                $job['started_at'] = $start->toDateTimeString();
                $job['completed_at'] = now()->toDateTimeString();
                $job['status'] = $event instanceof JobExceptionOccurred ? 'failed' : 'processed';
                $job['custom_context'] = DataHelper::getRedactedContext();
            }

            $jobs[] = $job;
        }

        $this->laritor->addEvents(static::$eventType, $jobs);

        if ($event->connectionName !== 'sync') {
            $this->laritor->sendEvents();
        }
    }

    /**
     * @param JobQueued|JobProcessing|JobProcessed|JobExceptionOccurred $event
     * @return mixed
     */
    private function resolveJobId($event)
    {
        try{
            if ($event instanceof JobQueued) {
                $jobPayload = [];
                /** @phpstan-ignore-next-line  */
                if (method_exists($event, 'payload')) {
                    $jobPayload = $event->payload();
                }
            } else {
                $jobPayload = $event->job->payload();
            }
        } catch (\Exception $e) {
            $jobPayload = [];
        }

        if (isset($jobPayload['uuid'])) {
            return $jobPayload['uuid'];
        }

        return $event instanceof JobQueued ? $event->id : $event->job->getJobId();
    }
}
