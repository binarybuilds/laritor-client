<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Str;


class OutboundRequestRecorder extends Recorder
{
    public static $eventType = 'outbound_requests';

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
        if ( Str::contains($event->request->url(), 'laritor.net') ||
            !FilterHelper::recordOutboundRequest($event->request->url())) {
            return;
        }

        $this->laritor->pushEvent(static::$eventType, [
            'started_at' => now(),
            'url' => $event->request->url(),
            'method' => $event->request->method(),
            'status' => 'sent',
            'context' => $this->laritor->getContext()
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

    /**
     * @param $outboundRequestEvent
     */
    public function completeOutboundRequest($outboundRequestEvent)
    {
        $outboundRequests = collect( $this->laritor->getEvents(static::$eventType))
            ->map(function ($request) use ($outboundRequestEvent){

            if ( $request['status'] === 'sent' && $request['url'] === $outboundRequestEvent->request->url() ) {
                $duration = $request['started_at']->diffInMilliseconds();
                return [
                    'started_at' => $request['started_at']->format('Y-m-d H:i:s'),
                    'completed_at' => now()->format('Y-m-d H:i:s'),
                    'duration' => $duration,
                    'code' => $outboundRequestEvent instanceof ResponseReceived ? $outboundRequestEvent->response->status() : 0,
                    'url' => $outboundRequestEvent->request->url(),
                    'method' => $outboundRequestEvent->request->method(),
                    'status' => 'completed',
                    'order' => $request['order'],
                    'context' => $request['context'],
                    'request' => [
                        'body' => $this->getRequestBody($outboundRequestEvent->request),
                        'headers' => $this->getRequestHeaders($outboundRequestEvent->request),
                    ],
                    'response' => [
                        'body' => $outboundRequestEvent instanceof ConnectionFailed ? false : $this->getResponseBody($outboundRequestEvent->response),
                        'headers' => $outboundRequestEvent instanceof ConnectionFailed ? false : $this->getResponseHeaders($outboundRequestEvent->response),
                    ]
                ];
            }

            return $request;
        })->values()->toArray();

        $this->laritor->addEvents(static::$eventType, $outboundRequests);
    }

    protected function getRequestBody(Request $request)
    {
        if (config('laritor.outbound_requests.body')) {
            return $request->isJson() ?
                DataHelper::redactArray(json_decode($request->body(), true)) :
                DataHelper::redactData($request->body());
        }

        return false;
    }

    protected function getRequestHeaders(Request $request)
    {
        if (config('laritor.outbound_requests.headers')) {
            return DataHelper::redactHeaders($request->headers());
        }

        return false;
    }

    protected function getResponseBody(Response $response)
    {
        if (config('laritor.outbound_requests.response_body')) {
            $body = $response->json();

            if (is_array($body)) {
                return DataHelper::redactArray($body);
            }

            return DataHelper::redactData($response->body());
        }

        return false;
    }

    protected function getResponseHeaders(Response $response)
    {
        if (config('laritor.outbound_requests.response_headers')) {
            return DataHelper::redactHeaders($response->headers());
        }

        return false;
    }
}
