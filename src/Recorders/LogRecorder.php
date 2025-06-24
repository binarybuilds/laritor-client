<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use Illuminate\Log\Events\MessageLogged;

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
        $this->laritor->pushEvent(static::$eventType, [
            'level' => $event->level,
            'message' => DataHelper::redactData($event->message),
            'log_context' => $event->context,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'context' => $this->laritor->getContext()
        ]);
    }
}
