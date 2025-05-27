<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Mail\Events\MessageSent;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

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
            ];
        }

        $eventData['id'] = $event->data['__laritor_id'] ?? '';
        $eventData['mailable'] = $event->data['__laritor_mailable'] ?? '';
        $eventData['context'] = $this->laritor->getContext();
        $eventData['started_at'] = now()->format('Y-m-d H:i:s');

        $this->laritor->pushEvent(static::$eventType, $eventData );
    }

    /**
     * @param MessageSent $event
     */
    public function sent(MessageSent $event)
    {
        $id = $event->data['__laritor_id'] ?? '';

        $events = collect($this->laritor->getEvents(static::$eventType))->map(function ($event) use ($id){
            if ($event['id'] === $id) {
                $event['completed_at'] = now()->format('Y-m-d H:i:s');
            }
            return $event;
        });

        $this->laritor->addEvents(static::$eventType, $events);
    }

    public static function registerRecorder()
    {
        parent::registerRecorder();

        $existing_callback = Mailable::$viewDataCallback;

        Mailable::buildViewDataUsing(function ($mailable) use ( $existing_callback ) {

            $data = [];

            if( $existing_callback ) {
                $data = call_user_func( $existing_callback, $mailable );

                if( ! is_array($data) ) $data = [];
            }

            return array_merge($data, [
                '__laritor_id' => Str::uuid(),
                '__laritor_mailable' => get_class($mailable),
                '__laritor_queued' => in_array(ShouldQueue::class, class_implements($mailable)),
            ]);
        });
    }
}
