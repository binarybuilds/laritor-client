<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Http\Client\Request;
use Illuminate\Http\Client\Response;


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
        $this->laritor->pushEvent(static::$eventType, [
            'started_at' => now(),
            'completed_at' => null,
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
                $started = $request['started_at'];
                $duration = $started->diffInMilliseconds();
                $status = $outboundRequestEvent instanceof ResponseReceived ? $outboundRequestEvent->response->status() : 0;
                $request['started_at'] = $started->format('Y-m-d H:i:s');
                $request['completed_at'] = now()->format('Y-m-d H:i:s');
                $request['duration'] = $duration;
                $request['code'] = $status;
                $request['status'] = 'completed';
                $request['request'] = [
                    'body' => $this->getRequestBody($outboundRequestEvent->request, $status, $duration),
                    'headers' => $this->getRequestHeaders($outboundRequestEvent->request, $status, $duration),
                ];
                $request['response'] = [
                    'body' => $outboundRequestEvent instanceof ConnectionFailed ? false : $this->getResponseBody($outboundRequestEvent->response, $outboundRequestEvent->request->url(), $status, $duration),
                    'headers' => $outboundRequestEvent instanceof ConnectionFailed ? false : $this->getResponseHeaders($outboundRequestEvent->response, $outboundRequestEvent->request->url(), $status, $duration),
                ];
            }

            return $request;
        })->values()->toArray();

        $this->laritor->addEvents(static::$eventType, $outboundRequests);
    }

    protected function getRequestBody(Request $request, $status, $duration)
    {
        if (FilterHelper::recordOutboundRequestBody($request->url(), $status, $duration)) {
            return $request->isJson() ?
                DataHelper::redactArray(json_decode($request->body(), true)) :
                DataHelper::redactData($request->body());
        }

        return [];
    }

    protected function getRequestHeaders(Request $request, $status, $duration)
    {
        if (FilterHelper::recordOutboundRequestHeaders($request->url(), $status, $duration)) {
            return DataHelper::redactHeaders($request->headers());
        }

        return [];
    }

    protected function getResponseBody(Response $response, $url, $status, $duration)
    {
        if (FilterHelper::recordOutboundRequestResponseBody($url, $status, $duration)) {
            $body = $response->json();

            if (is_array($body)) {
                return DataHelper::redactArray($body);
            }

            return DataHelper::redactData($response->body());
        }

        return [];
    }

    protected function getResponseHeaders(Response $response, $url, $status, $duration)
    {
        if (FilterHelper::recordOutboundRequestResponseHeaders($url, $status, $duration)) {
            return DataHelper::redactHeaders($response->headers());
        }

        return [];
    }
}
