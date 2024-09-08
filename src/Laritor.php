<?php

namespace Laritor\LaravelClient;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class Laritor
{
    /**
     * @var array
     */
    private $events = [];

    /**
     * @param $name
     * @param $event
     * @return $this
     */
    public function pushEvent($name, $event)
    {
        $this->events[ $name ][] = $event;
        return $this;
    }

    /**
     * @param $name
     * @param $events
     * @return $this
     */
    public function addEvents($name, $events)
    {
        $this->events[$name] = $events;
        return $this;
    }

    /**
     * @param $name
     * @return array|mixed
     */
    public function getEvents($name)
    {
        return isset($this->events[$name]) ? $this->events[$name] : [];
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $app = app();

        return [
            'app_key' => config('laritor.keys.backend', 'jhfdvhvhsdkdf'),
            'app' => url('/'),
            'version' => $app->version(),
            'event_at' => now(),
            'env' => config('app.env'),
            'php' => phpversion(),
            'server' => [
                'host' => gethostbyname(gethostname()),
                'os' => PHP_OS,
            ],
            'cache' => [
                'config' => $app->configurationIsCached(),
                'routes' => $app->routesAreCached(),
                'events' => $app->eventsAreCached()
            ],
            'events' => $this->events
        ];
    }

    /**
     * @return false|string
     */
    public function toJson()
    {
        return json_encode($this->toArray());
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->events = [];
    }

    /**
     * @return void
     */
    public function sendEvents()
    {
        $this->transformEvents();

        if ($this->shouldSendEvents()) {
            $this->callApi();
        }

        $this->reset();
    }

    public function transformEvents()
    {
        foreach ((array)config('laritor.recorders') as $recorder) {
            if (isset($this->events[$recorder::$eventType])) {
                $this->events[$recorder::$eventType] = $recorder::transformEvents(
                    $this->events[$recorder::$eventType]
                );
            }
        }
    }

    /**
     * @return void
     */
    public function callApi()
    {
        //todo: implement api
        Http::post('http:/159.223.153.239/api/events', $this->toArray());
    }

    /**
     * @param $health_checks
     * @param $scheduled_commands
     */
    public function discover($health_checks, $scheduled_commands)
    {
        $data = [
            'app_key' => config('laritor.keys.backend', 'jhfdvhvhsdkdf'),
            'env' => config('app.env'),
            'health_checks' => $health_checks,
            'scheduled_commands' => $scheduled_commands
        ];

        //todo: implement api
        Http::post('http:/159.223.153.239/api/discover', $data);
    }

    /**
     * @return bool
     */
    public function shouldSendEvents()
    {
        //todo: remove after testing
        return true;

        if (app()->runningInConsole() || ! $this->isRateLimiterEnabled() ) {
            return true;
        }

        $key = 'laritor-'.Str::slug(request()->path());
        if (! RateLimiter::tooManyAttempts($key, config('laritor.rate_limiter_attempts') ) ) {
            RateLimiter::hit($key);
            return true;
        }

        return false;
    }

    public function isRateLimiterEnabled()
    {
        return config('laritor.use_rate_limiter');
    }
}