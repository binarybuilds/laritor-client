<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;

class MailRecorder extends Recorder
{
    /**
     * @var string
     */
    public static $eventType = 'mails';

    /**
     * @var string[]
     */
    public static $events = [
        MessageSending::class,
        MessageSent::class,
    ];

    /**
     * @param MessageLogged $event
     * @return void
     */
    public function trackEvent($event)
    {
        if ($event instanceof MessageSending ) {
            $this->sending($event);
        }
        elseif ($event instanceof MessageSent ) {
            $this->sent($event);
        }
    }

    /**
     * @param MessageSending $event
     */
    public function sending(MessageSending $event)
    {
        $message = $event->message;

        if ($message instanceof \Swift_Message) {
            $eventData = [
                'to' => $message->getTo(),
                'cc' => $message->getCc(),
                'bcc' => $message->getBcc(),
                'from' => $message->getFrom(),
                'reply' => $message->getReplyTo(),
                'subject' => $message->getSubject(),
                'id' => $message->getId(),
                'context' => $this->laritor->getContext(),
                'started_at' => now()->format('Y-m-d H:i:s'),
            ];
        } else {
            $eventData = [
                'to' => implode(',', array_map(function ($address) {
                    return $address->getAddress();
                }, $message->getTo())),
                'cc' => implode(',', array_map(function ($address) {
                    return $address->getAddress();
                }, $message->getCc())),
                'bcc' => implode(',', array_map(function ($address) {
                    return $address->getAddress();
                }, $message->getBcc())),
                'from' => implode(',', array_map(function ($address) {
                    return $address->getAddress();
                }, $message->getFrom())),
                'reply' => implode(',', array_map(function ($address) {
                    return $address->getAddress();
                }, $message->getReplyTo())),
                'subject' => $message->getSubject(),
                'id' => $message->getHeaders()->get('Message-ID'),
                'context' => $this->laritor->getContext(),
                'started_at' => now()->format('Y-m-d H:i:s'),
            ];
        }

        $this->laritor->pushEvent(static::$eventType, $eventData );
    }

    /**
     * @param MessageSent $event
     */
    public function sent(MessageSent $event)
    {
        $message = $event->message;

        if ($message instanceof \Swift_Message) {
            $id = $message->getId();
        } else {
            $id = $message->getHeaders()->get('Message-ID');
        }

        $events = collect($this->laritor->getEvents(static::$eventType))->map(function ($event) use ($id){
            if ($event['id'] === $id) {
                $event['completed_at'] = now()->format('Y-m-d H:i:s');
            }
            return $event;
        });

        $this->laritor->addEvents(static::$eventType, $events);
    }
}
