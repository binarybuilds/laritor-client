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
        self::recordFeatureCheck($event->feature, $event->scope, $event->value !== false);
    }

    public static function registerRecorder()
    {
        if (class_exists(\Laravel\Pennant\Events\FeatureRetrieved::class)) {
            Event::listen( \Laravel\Pennant\Events\FeatureRetrieved::class, [static::class, 'handle'] );
        }
    }

    public static function recordFeatureCheck(string $feature, $scope = null, bool $active = true)
    {
        $laritor = app(Laritor::class);

        $laritor->pushEvent(self::$eventType, [
            'feature' => $feature,
            'active' => $active,
            'feature_flag_scope' => $scope,
            'context' => $laritor->getContext(),
            'checked_at' => now()->toDateTimeString(),
        ]);
    }
}
