<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Request;

//use Jenssegers\Agent\Agent;

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

//        $agent = new Agent();
        $startTime = defined('LARAVEL_START') ? LARAVEL_START : $event->request->server('REQUEST_TIME_FLOAT');
        $duration =  $startTime ? floor((microtime(true) - $startTime) * 1000) : null;

        if ($this->shouldRecordRequest($request, $duration)) {
            $data = [
                'type' => 'request',
                'request' => [
                    'method' => $request->method(),
                    'url' => $request->fullUrl(),
//                'headers' => $request->headers->all(),
//                'body' => $request->getContent(),
//                'query_parameters' => $request->query->all()
                ],
                'response' => [
                    'status_code' => $event->response->status(),
//                'headers' => $event->response->headers->all(),
                    'duration' => $duration,
                    'memory' => round(memory_get_peak_usage(true) / 1024 / 1024, 1),
                ],
                'user' => [
//                'authenticated' => $request->user(),
                    'ip' => $request->getClientIp(),
                    'user_agent' => $request->userAgent(),
//                'is_bot' => $agent->isRobot(),
//                'device' => $agent->device(),
//                'os' => $agent->platform(),
//                'os_version' => $agent->version( $agent->platform() ),
//                'browser' => $agent->browser(),
//                'browser_version' => $agent->version( $agent->browser() ),
//                'device_type' => $agent->isMobile() ? 'mobile' : ($agent->isTablet() ? 'tablet' : 'desktop'),
                ],
                'route' => [
                    'controller' => get_class(optional($request->route())->getController()),
//                'name' => optional($request->route())->getName(),
                    'method' => optional($request->route())->getActionMethod(),
//                'middleware' => array_values(optional($request->route())->gatherMiddleware())
                ],
            ];

            $this->laritor->addEvent($data);
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
