<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;

class SchedulerRecorder extends Recorder
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function start(CommandStarting $event)
    {
        if ( $event->command !== 'schedule:run' && $event->command !== 'schedule:finish') {
            return;
        }

        $data = [
            'type' => 'scheduler',
            'started_at' => now(),
            'completed_at' => null
        ];

        $this->laritor->addEvent($data);
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function finish(CommandFinished $event)
    {
        if ( $event->command !== 'schedule:run' && $event->command !== 'schedule:finish') {
            return;
        }

        $this->laritor->completeScheduler();
    }
}
