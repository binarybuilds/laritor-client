<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Support\Facades\Event;
use Laritor\LaravelClient\Laritor;

class Recorder
{
    /**
     * @var array
     */
    public static $events = [];

    /**
     * @var Laritor
     */
    protected $laritor;

    /**
     * @param Laritor $laritor
     */
    public function __construct(Laritor $laritor)
    {
        $this->laritor = $laritor;
    }

    /**
     * @param $event
     * @return void
     */
    public function handle($event)
    {
        try{
            $this->trackEvent($event);
        } catch (\Throwable $exception) {
            report($exception);

        }
    }

    public function trackEvent( $event )
    {
    }

    /**
     * @param Laritor $laritor
     * @return bool
     */
    public static function shouldReportEvents(Laritor $laritor)
    {
        return true;
    }

    /**
     * @return void
     */
    public static function registerRecorder()
    {
        foreach (static::$events as $event) {
            Event::listen( $event, [static::class, 'handle'] );
        }
    }
}
