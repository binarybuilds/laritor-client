<?php

namespace Laritor\LaravelClient;

use Illuminate\Console\Scheduling\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class Laritor
{
    /**
     * @var array
     */
    private $events = [];

    /**
     * @var array
     */
    private $queries = [];

    /**
     * @var array
     */
    public $outboundRequests = [];

    /**
     * @param array $event
     * @return $this
     */
    public function addEvent(array $event)
    {
        array_push($this->events, $event);
        return $this;
    }

    /**
     * @param array $request
     * @return $this
     */
    public function addOutboundRequest(array $request)
    {
        array_push($this->outboundRequests, $request);
        return $this;
    }


    /**
     * @param array $query
     * @return $this
     */
    public function addQuery(array $query)
    {
        array_push($this->queries, $query);
        return $this;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $app = app();
        
        if ($app->runningInConsole()) {
            $this->addConsoleEvent();
        }

        return [
            'app' => url('/'),
            'version' => $app->version(),
            'env' => config('app.env'),
            'run_time' => $app->runningInConsole() ? 'console' : 'web',
            'php' => phpversion(),
            'server' => [
                'host' => gethostbyname(gethostname()),
                'os' => PHP_OS,
                'webserver' => $app->runningInConsole() ? 'cli' : ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown'),
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
        $this->queries = [];
        $this->outboundRequests = [];
    }

    /**
     * @return void
     */
    public function sendEvents()
    {
        $this->updateQueryEvents();
        
        if ($this->shouldSendEvents()) {
            $this->callApi();
        }

        $this->reset();
    }

    private function updateQueryEvents()
    {
        if ($this->shouldReportQueries()) {
            $queries = collect($this->queries);

            $duplicates = $queries->whereIn('query_bindings', $queries->duplicates('query_bindings')->values())
                ->map(function ($query){
                    $query['issue'] = 'duplicate';
                    return $query;
                })->unique(function ($query) {
                    return $query['query_bindings'] . $query['file']. $query['line'];
                })->values()->toArray();

            $nplusone = $queries->whereIn('query', $queries->duplicates('query')->values())
                ->map(function ($query){
                    $query['issue'] = 'n-plus-1';
                    return $query;
                })->unique('query')->values()->toArray();

            $slow = $queries->where('time', '>=', config('laritor.query.slow') )
                ->map(function ($query){
                    $query['issue'] = 'slow';
                    return $query;
                })->unique()->values()->toArray();

            $this->events = array_merge($this->events, $duplicates, $nplusone, $slow );
        }

        unset($this->queries);

    }

    /**
     * @return bool
     */
    private function shouldReportQueries()
    {
        return true;
    }

    /**
     * @return void
     */
    public function callApi()
    {
        file_put_contents(app_path('/events.json'), $this->toJson() );
        dd(0);
    }
    
    private function addConsoleEvent()
    {
        $event = collect($this->events)->where('type', 'command')->first();

        if (! $event ) {
            array_push($this->events, [
                'type' => 'command',
                'command' => implode(' ', $_SERVER['argv']),
            ]);
        }
    }

    /**
     * @return bool
     */
    public function shouldSendEvents()
    {
        if (app()->runningInConsole() || ! $this->isRateLimiterEnabled() ) {
            return !empty($this->events);
        }

        $key = 'laritor-'.Str::slug(request()->path());
        if (! RateLimiter::tooManyAttempts($key, config('laritor.rate_limiter_attempts') ) ) {
            RateLimiter::hit($key);
            return !empty($this->events);
        }

        return !empty($this->events);
    }

    public function isRateLimiterEnabled()
    {
        return config('laritor.use_rate_limiter');
    }

    /**
     * @param Event $scheduleEvent
     * @param $status
     * @return void
     */
    public function completeScheduledTask(Event $scheduleEvent, $status)
    {
        foreach ( $this->events as &$event ) {

            if (
                $event['type'] === 'scheduled_command' &&
                $event['command'] === ( $scheduleEvent instanceof CallbackEvent ? 'Closure' : $scheduleEvent->command)
            ) {
                $event['status'] = $status;
                $event['duration'] = now()->diffInMilliseconds($event['started_at']);
                $event['completed_at'] = now()->toDateTimeString();
                $event['started_at'] = $event['started_at']->toDateTimeString();
                break;
            }
        }
    }

    public function completeScheduler()
    {
        foreach ( $this->events as &$event ) {

            if ( $event['type'] === 'scheduler' ) {
                $event['duration'] = now()->diffInMilliseconds($event['started_at']);
                $event['completed_at'] = now()->toDateTimeString();
                $event['started_at'] = $event['started_at']->toDateTimeString();
                break;
            }
        }
    }
}