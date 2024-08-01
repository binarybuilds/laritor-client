<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Str;


class OutboundRequestRecorder extends Recorder
{
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
        $data = [
            'type' => 'outbound_request',
            'started_at' => now(),
            'url' => $event->request->url(),
            'method' => $event->request->method(),
            'status' => 'sent'
        ];

        $this->laritor->addOutboundRequest($data);
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
        foreach ( $this->laritor->outboundRequests as &$request ) {

            if (
                $request['status'] === 'sent' &&
                $request['url'] === $outboundRequestEvent->request->url()
            ) {
                $duration = now()->diffInMilliseconds($request['started_at']);

                if ($this->recordOutboundRequest($outboundRequestEvent->request->url(), $duration)) {
                    $request['status'] = 'completed';

                    $this->laritor->addEvent([
                        'type' => 'outbound_request',
                        'started_at' => $request['started_at']->toDateTimeString(),
                        'completed_at' => now()->toDateTimeString(),
                        'duration' => $duration,
                        'code' => $outboundRequestEvent instanceof ResponseReceived ? $outboundRequestEvent->response->status() : 0,
                        'url' => $outboundRequestEvent->request->url(),
                        'method' => $outboundRequestEvent->request->method(),
                        'status' => 'completed'
                    ]);
                    break;
                }
            }
        }
    }

    public function recordOutboundRequest($request, $duration)
    {
        if (app()->runningInConsole() &&  config('laritor.outbound_requests.ignore_console_requests') ) {
            return false;
        }

        if ( $duration < config('laritor.outbound_requests.slow')) {
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
