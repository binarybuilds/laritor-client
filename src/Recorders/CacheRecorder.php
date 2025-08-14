<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
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
    ];

    /**
     * @param $event
     * @return void
     */
    public function trackEvent($event)
    {
        if ( Str::startsWith($event->key, 'laritor') || !FilterHelper::recordCacheHit($event->key)) {
            return;
        }

        $this->laritor->pushEvent(static::$eventType, [
            'key' => $event->key,
            'is_hit' => $event instanceof CacheHit,
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'context' => $this->laritor->getContext()
        ]);
    }
}
