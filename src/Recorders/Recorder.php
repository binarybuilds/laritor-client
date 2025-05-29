<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use BinaryBuilds\LaritorClient\Laritor;

class Recorder
{
    /**
     * @var
     */
    public static $eventType;

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

            if (app()->runningUnitTests()) {
                throw $exception;
            }

            // Exception occurred during ingest. Send the exception to laritor
            // and silently ignore so request continues.
            rescue(function () use ($exception) {
                Http::post(rtrim(config('laritor.ingest_url'),'/').'/ingest-exception', [
                    'env' => config('app.env'),
                    'version' => app()->version(),
                    'php' => phpversion(),
                    'data' => [
                        'exception' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString()
                    ]
                ]);
            }, null, false);
        }
    }

    public function trackEvent( $event )
    {
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
