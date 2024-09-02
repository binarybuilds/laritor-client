<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Str;


class OutboundRequestRecorder extends Recorder
{
    /**
     * @var string[]
     */
    public static $events = [
        RequestSending::class,
        ConnectionFailed::class,
        ResponseReceived::class
    ];

    /**
     * @param $event
     * @return void
     */
    public function trackEvent($event)
    {
        if ($event instanceof RequestSending ) {
            $this->sending($event);
        } elseif ($event instanceof ConnectionFailed ) {
            $this->failed($event);
        } elseif ($event instanceof ResponseReceived ) {
            $this->completed($event);
        }
    }

    /**
     * @param RequestSending $event
     * @return void
     */
    public function sending(RequestSending $event)
    {
        if ($this->shouldRecordOutboundRequest($event->request->url())) {
            $this->laritor->pushEvent('outbound_requests', [
                'started_at' => now(),
                'url' => $event->request->url(),
                'method' => $event->request->method(),
                'status' => 'sent'
            ]);
        }
    }

    /**
     * @param ResponseReceived $event
     * @return void
     */
    public function completed(ResponseReceived $event)
    {
        $this->completeOutboundRequest($event);
    }

    /**
     * @param ConnectionFailed $event
     * @return void
     */
    public function failed(ConnectionFailed $event)
    {
        $this->completeOutboundRequest($event);
    }

    public function completeOutboundRequest($outboundRequestEvent)
    {
        $outboundRequests = collect( $this->laritor->getEvents('outbound_requests'))
            ->map(function ($request) use ($outboundRequestEvent){

            if ( $request['status'] === 'sent' && $request['url'] === $outboundRequestEvent->request->url() ) {
                $duration = $request['started_at']->diffInMilliseconds();
                return [
                    'started_at' => $request['started_at']->toDateTimeString(),
                    'completed_at' => now()->toDateTimeString(),
                    'duration' => $duration,
                    'code' => $outboundRequestEvent instanceof ResponseReceived ? $outboundRequestEvent->response->status() : 0,
                    'url' => $outboundRequestEvent->request->url(),
                    'method' => $outboundRequestEvent->request->method(),
                    'status' => 'completed',
                    'slow' => $duration >= config('laritor.outbound_requests.slow')
                ];
            }

            return $request;
        })->values()->toArray();

        $this->laritor->addEvents('outbound_requests', $outboundRequests);
    }

    /**
     * @param $request
     * @return bool
     */
    public function shouldRecordOutboundRequest($request)
    {
        if (app()->runningInConsole() &&  config('laritor.outbound_requests.ignore_console_requests') ) {
            return false;
        }

        foreach ((array)config('laritor.outbound_requests.ignore') as $ignore) {
            if (Str::startsWith(rtrim($request, "/*"), $ignore) ) {
                return false;
            }
        }

        return true;
    }
}
