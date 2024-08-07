<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;

class RequestRecorder extends Recorder
{
    /**
     * Handle the event.
     *
     * @param  RequestHandled $event
     * @return void
     */
    public function trackEvent($event)
    {
        $request = $event->request;

        $startTime = defined('LARAVEL_START') ? LARAVEL_START : $event->request->server('REQUEST_TIME_FLOAT');
        $duration =  $startTime ? floor((microtime(true) - $startTime) * 1000) : null;

        if ($this->shouldRecordRequest($request, $duration)) {
            $this->laritor->pushEvent('requests', [
                'type' => 'request',
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
    }

    public function shouldRecordRequest(Request $request, $duration)
    {
        if ( $duration < config('laritor.requests.slow')) {
            return false;
        }

        foreach ((array)config('laritor.requests.ignore') as $ignore) {
            if ($request->is($ignore)) {
                return false;
            }
        }

        return true;
    }
}
