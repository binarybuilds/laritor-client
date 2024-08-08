<?php

namespace Laritor\LaravelClient;

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
            'app' => url('/'),
            'version' => $app->version(),
            'env' => config('app.env'),
            'run_time' => $app->runningInConsole() ? 'console' : 'web',
            'command' => $app->runningInConsole() ? implode( ' ', $_SERVER[ 'argv' ] ) : '',
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
        if ($this->shouldSendEvents()) {

            $this->callApi();
        }

        $this->reset();
    }

    /**
     * @return void
     */
    public function callApi()
    {
        //todo: implement api
        file_put_contents(app_path('/'.Str::random(6).'.json'), $this->toJson() );
        dd(0);
    }

    /**
     * @return bool
     */
    public function shouldSendEvents()
    {
        $report = false;
        foreach ((array)config('laritor.recorders') as $recorder) {

            if ( $recorder::shouldReportEvents($this) ) {
                $report = true;
            }
        }

        //todo: remove after testing
        return true;

        if (!$report) {
            return false;
        }


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