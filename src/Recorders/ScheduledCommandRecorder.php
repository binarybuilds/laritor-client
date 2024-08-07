<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;
use Illuminate\Http\Client\Events\ResponseReceived;

class ScheduledCommandRecorder extends Recorder
{
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
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function skip(ScheduledTaskSkipped $event)
    {
        $event = $event->task;
        $this->completeScheduledTask($event, 'skipped');
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
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
                    $command['duration'] = now()->diffInMilliseconds($command['started_at']);
                    $command['completed_at'] = now()->toDateTimeString();
                    $command['started_at'] = $command['started_at']->toDateTimeString();
                }

                return $command;
            })->values()->toArray();

        $this->laritor->addEvents('scheduled_commands', $scheduledTasks);
    }
}
