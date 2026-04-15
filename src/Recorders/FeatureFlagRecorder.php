<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use BinaryBuilds\LaritorClient\Laritor;
use Illuminate\Support\Facades\Event;

class FeatureFlagRecorder extends Recorder
{
    /**
     * @var string
     */
    public static $eventType = 'feature_flags';

    /**
     * @param mixed $event
     * @return void
     */
    public function trackEvent($event)
    {
        if(!FilterHelper::recordFeatureFlag($event->feature, $event->scope)) {
            return;
        }

        self::recordFeatureCheck($event->feature, $event->value !== false);
    }

    public static function registerRecorder()
    {
        if (class_exists(\Laravel\Pennant\Events\FeatureRetrieved::class)) {
            Event::listen( \Laravel\Pennant\Events\FeatureRetrieved::class, [static::class, 'handle'] );
        }
    }

    public static function recordFeatureCheck(string $feature, bool $active = true)
    {
        $laritor = app(Laritor::class);

        $laritor->pushEvent(self::$eventType, [
            'feature' => $feature,
            'active' => $active,
            'context' => $laritor->getContext(),
            'checked_at' => now()->toDateTimeString(),
        ]);
    }
}
