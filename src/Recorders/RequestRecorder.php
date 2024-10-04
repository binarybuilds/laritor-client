<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;

class RequestRecorder extends Recorder
{
    /**
     * @var string
     */
    public static $eventType = 'requests';

    /**
     * @var string[]
     */
    public static $events = [
        RequestHandled::class
    ];

    /**
     * Handle the event.
     *
     * @param  RequestHandled $event
     * @return void
     */
    public function trackEvent($event)
    {
        $request = $event->request;

        if ( ! $this->shouldRecordRequest($request)) {
            return;
        }

        $startTime = defined('LARAVEL_START') ? LARAVEL_START : $event->request->server('REQUEST_TIME_FLOAT');
        $duration =  $startTime ? floor((microtime(true) - $startTime) * 1000) : null;

        $this->laritor->pushEvent(static::$eventType, [
            'started_at' => now()->subMilliseconds($duration)->toDateTimeString(),
            'completed_at' => now()->toDateTimeString(),
            'slow' => $duration >= config('laritor.requests.slow'),
            'duration' => $duration,
            'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 1),
            'request' => [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'headers' => $request->headers
            ],
            'response' => [
                'status_code' => $event->response->status(),
            ],
            'user' => [
                'authenticated' => [
                    'name' => $request->user() ? $request->user()->name : '',
                    'email' => $request->user() ? $request->user()->email : '',
                ],
                'ip' => $request->getClientIp(),
                'user_agent' => $request->userAgent(),
            ],
            'route' => [
                'controller' => optional($request->route())->getController() ? get_class(optional($request->route())->getController()) : 'callback',
                'method' => optional($request->route())->getActionMethod(),
            ],
        ]);
    }

    public function shouldRecordRequest(Request $request)
    {
        foreach ((array)config('laritor.requests.ignore') as $ignore) {
            if ($request->is($ignore)) {
                return false;
            }
        }

        return true;
    }
}
