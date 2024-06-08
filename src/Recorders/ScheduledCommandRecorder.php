<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Console\Events\ScheduledTaskFailed;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskSkipped;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Console\Scheduling\CallbackEvent;

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

        $data = [
            'type' => 'scheduled_command',
            'started_at' => now(),
            'command' => $event instanceof CallbackEvent ? 'Closure' : $event->command,
            'expression' => $event->expression,
            'timezone' => $event->timezone,
            'user' => $event->user,
            'status' => 'started'
        ];

        $this->laritor->addEvent($data);
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
        $this->laritor->completeScheduledTask($event, 'completed');
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
        $this->laritor->completeScheduledTask($event, 'skipped');
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
        $this->laritor->completeScheduledTask($event, 'failed');
    }
}
