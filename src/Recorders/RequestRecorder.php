<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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

        $this->laritor->responseRenderCompleted();

        $startTime = defined('LARAVEL_START') ? LARAVEL_START : $event->request->server('REQUEST_TIME_FLOAT');
        $duration =  $startTime ? floor((microtime(true) - $startTime) * 1000) : null;

        $this->laritor->pushEvent(static::$eventType, [
            'request' => [
//                'started_at' => now()->subMilliseconds($duration)->format('Y-m-d H:i:s'),
                'completed_at' => now()->format('Y-m-d H:i:s'),
                'duration' => $duration,
                'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 1),
                'url' => $request->path(),
                'size' => strlen($request->getContent())
            ],
            'response' => [
                'status_code' => $event->response->status(),
                'size' => strlen($event->response->getContent())
            ],
            'user' => [
                'authenticated' => [
                    'id' => Auth::id(),
                    'name' => $request->user() ? $request->user()->name : '',
                    'email' => $request->user() ? $request->user()->email : '',
                ],
                'ip' => $request->getClientIp(),
                'user_agent' => $request->userAgent(),
            ],
            'route' => [
                'name' => optional($request->route())->getName(),
                'uri' => optional($request->route())->uri(),
                'controller' => optional($request->route())->getController() ? get_class(optional($request->route())->getController()) : 'closure',
                'controller_method' => optional($request->route())->getActionMethod(),
                'method' => $request->method(),
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
