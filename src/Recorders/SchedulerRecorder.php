<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Laritor\LaravelClient\Laritor;

class SchedulerRecorder extends Recorder
{
    public static $events = [
        CommandStarting::class,
        CommandFinished::class
    ];

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
        $this->laritor->pushEvent('scheduler',  [
            'started_at' => now(),
            'completed_at' => null
        ]);
    }

    /**
     * @return void
     */
    public function finish()
    {
        $scheduler = $this->laritor->getEvents('scheduler');
        $scheduler = isset($scheduler[0]) ? $scheduler[0] : null;

        if ($scheduler) {
            $scheduler['duration'] = now()->diffInMilliseconds($scheduler['started_at']);
            $scheduler['completed_at'] = now()->toDateTimeString();
            $scheduler['started_at'] = $scheduler['started_at']->toDateTimeString();
        }

        $this->laritor->addEvents('scheduler', [$scheduler]);
    }

    /**
     * @param $event
     * @return bool
     */
    private function isSchedulerCommand($event)
    {
        return $event->command === 'schedule:run' || $event->command === 'schedule:finish';
    }

    /**
     * @param Laritor $laritor
     * @return bool
     */
    public static function shouldReportEvents( Laritor $laritor )
    {
        return true;
    }
}
