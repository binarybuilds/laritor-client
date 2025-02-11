<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;

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
     * @param  object  $event
     * @return void
     */
    public function start(ScheduledTaskStarting $event)
    {
        $event = $event->task;

        $this->laritor->pushEvent(static::$eventType, [
            'started_at' => now(),
            'command' => $event instanceof CallbackEvent ? 'Closure' : $event->command,
            'expression' => $event->expression,
            'timezone' => $event->timezone,
            'user' => $event->user,
            'status' => 'started'
        ]);
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
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

        $this->laritor->pushEvent(static::$eventType, [
            'started_at' => now()->format('Y-m-d H:i:s'),
            'completed_at' => now()->format('Y-m-d H:i:s'),
            'duration' => 0,
            'command' => $event instanceof CallbackEvent ? 'Closure' : $event->command,
            'expression' => $event->expression,
            'timezone' => $event->timezone,
            'user' => $event->user,
            'status' => 'skipped'
        ]);
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
            ->map(function ($command) use ($event, $status){

                if (
                    $command['command'] === ( $event instanceof CallbackEvent ? 'Closure' : $event->command)
                ) {
                    $command['status'] = $status;
                    $command['duration'] = $command['started_at']->diffInMilliseconds();
                    $command['completed_at'] = now()->format('Y-m-d H:i:s');
                    $command['started_at'] = $command['started_at']->format('Y-m-d H:i:s');
                }

                return $command;
            })->values()->toArray();

        $this->laritor->addEvents(static::$eventType, $scheduledTasks);
    }
}
