<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;
use Laritor\LaravelClient\Laritor;

class RequestRecorder extends Recorder
{
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

        $this->laritor->pushEvent('requests', [
            'slow' => $duration >= config('laritor.requests.slow'),
            'request' => [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
            ],
            'response' => [
                'status_code' => $event->response->status(),
                'duration' => $duration,
                'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 1),
            ],
            'user' => [
                'ip' => $request->getClientIp(),
                'user_agent' => $request->userAgent(),
            ],
            'route' => [
                'controller' => get_class(optional($request->route())->getController()),
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

    /**
     * @param Laritor $laritor
     * @return bool
     */
    public static function shouldReportEvents( Laritor $laritor )
    {
        return collect( $laritor->getEvents('requests'))
            ->where('slow', true)
            ->isNotEmpty();
    }
}
