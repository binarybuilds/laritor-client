<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Support\Str;
use Laritor\LaravelClient\Laritor;


class OutboundRequestRecorder extends Recorder
{
    public function __construct( Laritor $laritor )
    {
        parent::__construct( $laritor );
        $laritor->registerPrepareCallBack([OutboundRequestRecorder::class, 'removeInvalidEvents']);
    }

    /**
     * @param Laritor $laritor
     * @return void
     */
    public static function removeInvalidEvents(Laritor $laritor)
    {
        $laritor->addEvents('outbound_requests', collect( $laritor->getEvents('outbound_requests'))
            ->where('status', '!=', 'sent')
            ->values()
            ->toArray()
        );
    }

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
        $this->laritor->pushEvent('outbound_requests', [
            'started_at' => now(),
            'url' => $event->request->url(),
            'method' => $event->request->method(),
            'status' => 'sent'
        ]);
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
                $duration = now()->diffInMilliseconds($request['started_at']);

                if ($this->shouldRecordOutboundRequest($outboundRequestEvent->request->url(), $duration)) {
                    return [
                        'started_at' => $request['started_at']->toDateTimeString(),
                        'completed_at' => now()->toDateTimeString(),
                        'duration' => $duration,
                        'code' => $outboundRequestEvent instanceof ResponseReceived ? $outboundRequestEvent->response->status() : 0,
                        'url' => $outboundRequestEvent->request->url(),
                        'method' => $outboundRequestEvent->request->method(),
                        'status' => 'completed'
                    ];
                }
            }

            return $request;
        })->values()->toArray();

        $this->laritor->addEvents('outbound_requests', $outboundRequests);
    }

    /**
     * @param $request
     * @param $duration
     * @return bool
     */
    public function shouldRecordOutboundRequest($request, $duration)
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
