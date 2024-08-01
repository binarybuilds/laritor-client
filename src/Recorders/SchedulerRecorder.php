<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;

class SchedulerRecorder extends Recorder
{
    /**
     * @param $event
     * @return void
     */
    public function trackEvent($event)
    {
        if (!$this->isSchedulerCommand($event)) {
            return;
        }

        if ($event instanceof CommandStarting ) {
            $this->start();
        } elseif ($event instanceof CommandFinished ) {
            $this->finish();
        }
    }

    /**
     * @return void
     */
    public function start()
    {
        $data = [
            'type' => 'scheduler',
            'started_at' => now(),
            'completed_at' => null
        ];

        $this->laritor->addEvent($data);
    }

    /**
     * @return void
     */
    public function finish()
    {
        $this->laritor->completeScheduler();
    }

    /**
     * @param $event
     * @return bool
     */
    private function isSchedulerCommand($event)
    {
        return $event->command === 'schedule:run' || $event->command === 'schedule:finish';
    }
}
