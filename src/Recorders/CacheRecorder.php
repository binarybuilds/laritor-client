<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Support\Str;

class CacheRecorder extends Recorder
{
    /**
     * @var string
     */
    public static $eventType = 'caches';

    /**
     * @var string[]
     */
    public static $events = [
        CacheHit::class,
        CacheMissed::class,
        KeyWritten::class,
        KeyForgotten::class,
    ];

    /**
     * @param $event
     * @return void
     */
    public function trackEvent($event)
    {
        if ( Str::startsWith($event->key, ['laritor']) ||
            !FilterHelper::recordCacheHit($event->key)
        ) {
            return;
        }

        $type = null;
        if ($event instanceof CacheHit) {
            $type = 'HIT';
        } elseif ($event instanceof CacheMissed) {
            $type = 'MISS';
        } elseif ($event instanceof KeyWritten) {
            $type = 'WRITE';
        } elseif ($event instanceof KeyForgotten) {
            $type = 'DELETE';
        }

        $this->laritor->pushEvent(static::$eventType, [
            'key' => $event->key,
            'type' => $type,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'context' => $this->laritor->getContext()
        ]);
    }
}
