<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;


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

        $this->laritor->addEvent($data);
    }

    /**
     * @param ResponseReceived $event
     * @return void
     */
    public function completed(ResponseReceived $event)
    {
        $this->laritor->completeOutboundRequest($event);
    }

    /**
     * @param ConnectionFailed $event
     * @return void
     */
    public function failed(ConnectionFailed $event)
    {
        $this->laritor->completeOutboundRequest($event);
    }
}
