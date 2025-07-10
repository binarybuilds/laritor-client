<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;

class LogRecorder extends Recorder
{
    /**
     * @var string
     */
    public static $eventType = 'logs';

    /**
     * @var string[]
     */
    public static $events = [
        MessageLogged::class
    ];

    /**
     * @param MessageLogged $event
     * @return void
     */
    public function trackEvent($event)
    {
        if(!$this->shouldRecordLog($event)) {
            return;
        }

        $this->laritor->pushEvent(static::$eventType, [
            'level' => $event->level,
            'message' => DataHelper::redactData($event->message),
            'log_context' => $event->context,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'context' => $this->laritor->getContext()
        ]);
    }

    public function shouldRecordLog($event)
    {
        $levels = [
            'DEBUG' => 1,
            'NOTICE' => 2,
            'INFO' => 3,
            'WARNING' => 4,
            'ERROR' => 5,
            'ALERT' => 6,
            'CRITICAL' => 7,
            'EMERGENCY' => 8
        ];

        $minIndex = $levels[strtoupper(config('laritor.log_level'))];
        $logIndex = $levels[strtoupper($event->level)];

        return $logIndex >= $minIndex;
    }
}
