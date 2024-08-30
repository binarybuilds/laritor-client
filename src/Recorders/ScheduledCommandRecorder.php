<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Support\Str;
use Laritor\LaravelClient\Laritor;

class ScheduledCommandRecorder extends Recorder
{
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

        $this->laritor->pushEvent('scheduled_commands', [
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

        $this->laritor->pushEvent('scheduled_commands', [
            'started_at' => now()->toDateTimeString(),
            'completed_at' => now()->toDateTimeString(),
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
        $scheduledTasks = collect( $this->laritor->getEvents('scheduled_commands'))
            ->map(function ($command) use ($event, $status){

                if (
                    $command['command'] === ( $event instanceof CallbackEvent ? 'Closure' : $event->command)
                ) {
                    $command['status'] = $status;
                    $command['duration'] = now()->diffInSeconds($command['started_at']);
                    $command['completed_at'] = now()->toDateTimeString();
                    $command['started_at'] = $command['started_at']->toDateTimeString();
                }

                return $command;
            })->values()->toArray();

        $this->laritor->addEvents('scheduled_commands', $scheduledTasks);
    }

    public static function shouldReportEvents( Laritor $laritor )
    {
        if (!empty($laritor->getEvents('scheduled_commands'))) {
            $laritor->addEvents('commands', [] );
        }

        return true;
    }
}
