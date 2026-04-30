<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

class ScheduledTaskRecorder extends Recorder
{
    public static $eventType = 'scheduled_tasks';

    public static $events = [
        ScheduledTaskStarting::class,
        ScheduledTaskFinished::class,
        ScheduledTaskSkipped::class,
        ScheduledTaskFailed::class
    ];

    /**
     * @param $event
     * @return void
     */
    public function trackEvent($event)
    {
        $task = Str::substr(
            Str::replace("'",'', $event->task->command),
            mb_strpos(Str::replace("'",'', $event->task->command), 'artisan')
        );

        if (
            in_array($task, ['artisan laritor:send-metrics', 'artisan laritor:sync']) ||
            !FilterHelper::recordCommandOrScheduledTask($event->task->command)
        ) {
            return;
        }

        if ($event instanceof ScheduledTaskStarting ) {
            $this->start($event);
        } elseif ($event instanceof ScheduledTaskFinished ) {
            $event = $event->task;
            if($event->exitCode === 0) {
                $this->completeScheduledTask($event, 'completed');
            } else {
                $this->completeScheduledTask($event, 'failed');
            }
        } elseif ($event instanceof ScheduledTaskSkipped ) {
            $this->skip($event);
        } elseif ($event instanceof ScheduledTaskFailed ) {
            $event = $event->task;
            $this->completeScheduledTask($event, 'failed');
        }
    }

    /**
     * Handle the event.
     *
     * @param  ScheduledTaskStarting $event
     * @return void
     */
    public function start(ScheduledTaskStarting $event)
    {
        if (class_exists(\Illuminate\Support\Facades\Context::class)) {
            Context::add('laritor_scheduled_task_id', Str::uuid()->toString());
        }

        $event = $event->task;

        $payload = [
            'task' => $event instanceof CallbackEvent ? 'Closure' : $event->command,
            'started_at' => now(),
            'expression' => $event->expression,
            'timezone' => $event->timezone,
            'user' => $event->user,
            'status' => 'started',
            'scheduled_at_timestamp' => microtime(true),
        ];

        $scheduler = $this->laritor->getEvents(SchedulerRecorder::$eventType);

        if (isset($scheduler[0]['timestamp'])) {
            $payload['scheduled_at_timestamp'] = $scheduler[0]['timestamp'];
        }

        $this->laritor->pushEvent(static::$eventType, $payload);
    }

    /**
     * @param ScheduledTaskSkipped $event
     * @return void
     */
    public function skip(ScheduledTaskSkipped $event)
    {
        $event = $event->task;

        $payload = [
            'started_at' => now()->format('Y-m-d H:i:s'),
            'duration' => 0,
            'task' => $event instanceof CallbackEvent ? 'Closure' : $event->command,
            'expression' => $event->expression,
            'timezone' => $event->timezone,
            'user' => $event->user,
            'status' => 'skipped',
            'custom_context' => DataHelper::getRedactedContext(),
            'scheduled_at_timestamp' => microtime(true),
        ];

        $scheduler = $this->laritor->getEvents(SchedulerRecorder::$eventType);

        if (isset($scheduler[0]['timestamp'])) {
            $payload['scheduled_at_timestamp'] = $scheduler[0]['timestamp'];
        }

        $this->laritor->pushEvent(static::$eventType, $payload);

        $this->sendEvents();
    }

    public function completeScheduledTask($event, $status)
    {
        $scheduledTasks = collect( $this->laritor->getEvents(static::$eventType))
            ->map(function ($task) use ($event, $status){

                if (
                    $task['task'] === ( $event instanceof CallbackEvent ? 'Closure' : $event->command)
                    && $task['status'] === 'started'
                ) {
                    $task['status'] = $status;
                    $task['duration'] = $task['started_at']->diffInMilliseconds();
                    $task['completed_at'] = now()->format('Y-m-d H:i:s');
                    $task['started_at'] = $task['started_at']->format('Y-m-d H:i:s');
                    $task['custom_context'] = DataHelper::getRedactedContext();
                }

                return $task;
            })->values()->toArray();

        if (!empty($scheduledTasks)) {
            $this->laritor->addEvents(static::$eventType, $scheduledTasks);

            $this->sendEvents();
        }
    }

    public function sendEvents()
    {
        $scheduler = $this->laritor->getEvents(SchedulerRecorder::$eventType);
        $this->laritor->removeScheduler();
        $this->laritor->sendEvents();
        if (!empty($scheduler)) {
            $this->laritor->addEvents(SchedulerRecorder::$eventType, $scheduler);
        }
    }
}
