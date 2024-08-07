<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Cache\Events\CacheHit;

class CacheRecorder extends Recorder
{
    /**
     * @param $event
     * @return void
     */
    public function trackEvent($event)
    {
        $this->laritor->pushEvent('cache',[
            'key' => $event->key,
            'is_hit' => $event instanceof CacheHit
        ]);
    }
}
