<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Cache\Events\RetrievingKey;
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
        $type = null;
        if (class_exists(RetrievingKey::class) && $event instanceof RetrievingKey) {
            $type = 'RETRIEVING';
        }
        elseif ($event instanceof CacheHit) {
            $type = 'HIT';
        } elseif ($event instanceof CacheMissed) {
            $type = 'MISS';
        } elseif ($event instanceof KeyWritten) {
            $type = 'WRITE';
        } elseif ($event instanceof KeyForgotten) {
            $type = 'DELETE';
        }

        $eventFound = false;
        if ($type !== 'RETRIEVING') {
            $events = collect($this->laritor->getEvents(static::$eventType))
                ->map(function ($added) use ($event, $type, &$eventFound) {
                if ($added['type'] === 'RETRIEVING' && $added['key'] === $event->key) {
                    $eventFound = true;
                    $added['type'] = $type;
                    $added['duration'] = microtime(true) - $added['timestamp'];
                }
                return $added;
            });

            $this->laritor->addEvents(static::$eventType, $events);
        }

        if (!$eventFound) {
            $this->laritor->pushEvent(static::$eventType, [
                'key' => $event->key,
                'type' => $type,
                'store' => $event->storeName,
                'duration' => 0,
                'occurred_at' => now()->format('Y-m-d H:i:s'),
                'context' => $this->laritor->getContext()
            ]);
        }
    }
}
