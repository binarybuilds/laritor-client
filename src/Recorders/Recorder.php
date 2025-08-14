<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use BinaryBuilds\LaritorClient\Laritor;
use Illuminate\Support\Str;

class Recorder
{
    /**
     * @var string
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

            // Exception occurred during ingestion. Send the exception to laritor
            // and silently ignore the exception to let the request continue.
            rescue(function () use ($exception) {
                $data = json_encode([
                    'env' => !empty(config('laritor.env')) ? config('laritor.env') : config('app.env'),
                    'version' => app()->version(),
                    'php' => phpversion(),
                    'data' => [
                        'exception' => $exception->getMessage(),
                        'trace' => $exception->getTraceAsString()
                    ]
                ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
                Http::withBody($data, 'application/json')
                    ->withHeaders([
                        'X-Api-Key' => config('laritor.keys.backend')
                    ])
                    ->withUserAgent('laritor-client')
                    ->post(rtrim(config('laritor.ingest_endpoint'),'/').'/ingest-exception');
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
