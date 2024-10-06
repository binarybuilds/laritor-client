<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;

class SchedulerRecorder extends Recorder
{
    /**
     * @var string
     */
    public static $eventType = 'scheduler';

    /**
     * @var string[]
     */
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
        $this->laritor->pushEvent(static::$eventType,  [
            'started_at' => now(),
            'completed_at' => null
        ]);
    }

    /**
     * @return void
     */
    public function finish()
    {
        $scheduler = $this->laritor->getEvents(static::$eventType);
        $scheduler = isset($scheduler[0]) ? $scheduler[0] : null;

        if ($scheduler) {
            $scheduler['duration'] = $scheduler['started_at']->diffInMilliseconds();
            $scheduler['completed_at'] = now()->format('Y-m-d H:i:s.u');
            $scheduler['started_at'] = $scheduler['started_at']->format('Y-m-d H:i:s.u');
            $this->laritor->addEvents(static::$eventType, [$scheduler]);
        }
    }

    /**
     * @param $event
     * @return bool
     */
    private function isSchedulerCommand($event)
    {
        return $event->command === 'schedule:run';
    }
}
