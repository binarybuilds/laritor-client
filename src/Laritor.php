<?php

namespace BinaryBuilds\LaritorClient;

use Illuminate\Container\Container;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use BinaryBuilds\LaritorClient\Recorders\SchedulerRecorder;

class Laritor
{
    /**
     * @var array
     */
    private $events = [];

    private $order = 0;

    private $started = 0;

    private $booted = 0;

    private $middleware = 0;

    private $controller = 0;

    private $response = 0;

    private $context = 'BOOT';

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param string $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    public function started()
    {
        $this->started = defined('LARAVEL_START') ? LARAVEL_START : request()->server('REQUEST_TIME_FLOAT');
    }

    public function booted()
    {
        $this->booted = $this->started ? $this->getDurationFrom($this->started) : 0;

        if ( App::runningInConsole() ) {
            $this->setContext('COMMAND');
        } else {
            $this->setContext('MIDDLEWARE');
        }
    }

    public function controllerStarted()
    {
        $this->middleware = $this->getDurationFrom($this->started) - $this->booted;
        $this->setContext('CONTROLLER');
    }

    public function responseRenderStarted()
    {
        if ($this->context !== 'RESPONSE') {
            $this->controller = $this->getDurationFrom($this->started) - ($this->booted + $this->middleware );
            $this->setContext('RESPONSE');
        }
    }

    public function responseRenderCompleted()
    {
        $this->response = $this->getDurationFrom($this->started) - (
            $this->booted + $this->middleware + $this->controller
            );
    }

    public function getDurationFrom($time)
    {
        return floor((microtime(true) - $time) * 1000);
    }

    /**
     * @param $name
     * @param $event
     * @return $this
     */
    public function pushEvent($name, $event)
    {
        $event['order'] = $this->order;
        $this->order++;
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

    public function removeScheduler()
    {
        unset($this->events[SchedulerRecorder::$eventType]);
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
        return [
            'app' => url('/'),
            'env' => config('app.env'),
            'event_at' => now()->toDateTimeString(),
            'server' => [
                'host' => config('laritor.serverless') ? 'serverless' : config('laritor.server_name', gethostname()),
            ],
            'events' => $this->events,
            'booted' => $this->booted,
            'middleware' => $this->middleware,
            'controller' => $this->controller,
            'response' => $this->response
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
        $this->order = 0;
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
        rescue(function () {
            Http::post(  rtrim(config('laritor.ingest_url'),'/').'/events', $this->toArray());
        }, null, false);
    }

    /**
     * @param $data
     */
    public function sync($data)
    {
        rescue(function () use ($data) {
            $app = app();
            Http::post(rtrim(config('laritor.ingest_url'),'/').'/sync', [
                'env' => config('app.env'),
                'app' => url('/'),
                'version' => $app->version(),
                'php' => phpversion(),
                'server' => [
                    'host' => config('laritor.serverless') ? 'serverless' : config('laritor.server_name', gethostname()),
                    'os' => PHP_OS,
                ],
                'cache' => [
                    'config' => $app->configurationIsCached(),
                    'routes' => $app->routesAreCached(),
                    'events' => $app->eventsAreCached()
                ],
                'data' => $data
            ]);
        }, null, false);
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
        if (! RateLimiter::tooManyAttempts($key, config('laritor.requests.rate_limit.attempts') ) ) {
            RateLimiter::hit($key);
            return true;
        }

        return false;
    }

    public function isRateLimiterEnabled()
    {
        return (bool)config('laritor.requests.rate_limit.enabled', false);
    }
}
