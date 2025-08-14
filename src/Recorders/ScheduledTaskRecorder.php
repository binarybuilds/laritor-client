<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Support\Str;

class ScheduledTaskRecorder extends Recorder
{
    public static $eventType = 'scheduled_tasks';

    public static $events = [
        ScheduledTaskStarting::class,
        ScheduledTaskFinished::class
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
            $this->finish($event);
        } elseif ($event instanceof ScheduledTaskSkipped ) {
            $this->skip($event);
        } elseif ($event instanceof ScheduledTaskFailed ) {
            $this->fail($event);
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
        $event = $event->task;

        $scheduler = $this->laritor->getEvents(SchedulerRecorder::$eventType);

        $this->laritor->pushEvent(static::$eventType, [
            'scheduled_at' => $scheduler[0]['started_at']->startOfMinute()->format('Y-m-d H:i:s'),
            'started_at' => now(),
            'task' => $event instanceof CallbackEvent ? 'Closure' : $event->command,
            'expression' => $event->expression,
            'timezone' => $event->timezone,
            'user' => $event->user,
            'status' => 'started'
        ]);
    }

    /**
     * Handle the event.
     *
     * @param  ScheduledTaskFinished  $event
     * @return void
     */
    public function finish(ScheduledTaskFinished $event)
    {
        $event = $event->task;
        $this->completeScheduledTask($event, 'completed');
    }

    /**
     * @param ScheduledTaskSkipped $event
     * @return void
     */
    public function skip(ScheduledTaskSkipped $event)
    {
        $event = $event->task;

        $scheduler = $this->laritor->getEvents(SchedulerRecorder::$eventType);

        $this->laritor->pushEvent(static::$eventType, [
            'scheduled_at' => $scheduler[0]['started_at']->startOfMinute()->format('Y-m-d H:i:s'),
            'started_at' => now()->format('Y-m-d H:i:s'),
            'completed_at' => now()->format('Y-m-d H:i:s'),
            'duration' => 0,
            'task' => $event instanceof CallbackEvent ? 'Closure' : $event->command,
            'expression' => $event->expression,
            'timezone' => $event->timezone,
            'user' => $event->user,
            'status' => 'skipped',
            'custom_context' => DataHelper::getRedactedContext()
        ]);

        $this->sendEvents();
    }

    /**
     * @param ScheduledTaskFailed $event
     * @return void
     */
    public function fail(ScheduledTaskFailed $event)
    {
        $event = $event->task;
        $this->completeScheduledTask($event, 'failed');
    }

    public function completeScheduledTask($event, $status)
    {
        $scheduledTasks = collect( $this->laritor->getEvents(static::$eventType))
            ->map(function ($task) use ($event, $status){

                if (
                    $task['task'] === ( $event instanceof CallbackEvent ? 'Closure' : $event->command)
                ) {
                    $task['status'] = $status;
                    $task['duration'] = $task['started_at']->diffInMilliseconds();
                    $task['completed_at'] = now()->format('Y-m-d H:i:s');
                    $task['started_at'] = $task['started_at']->format('Y-m-d H:i:s');
                    $task['custom_context'] = DataHelper::getRedactedContext();
                }

                return $task;
            })->values()->toArray();

        $this->laritor->addEvents(static::$eventType, $scheduledTasks);

        $this->sendEvents();
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
