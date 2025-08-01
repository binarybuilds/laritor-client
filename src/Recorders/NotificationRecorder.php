<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\Events\NotificationSending;
use Illuminate\Notifications\Events\NotificationSent;

/**
 * Class NotificationRecorder
 * @package BinaryBuilds\LaritorClient\Recorders
 */
class NotificationRecorder extends Recorder
{
    /**
     * @var string
     */
    public static $eventType = 'notifications';

    /**
     * @var string[]
     */
    public static $events = [
        NotificationSending::class,
        NotificationSent::class,
    ];

    /**
     * @param MessageLogged $event
     * @return void
     */
    public function trackEvent($event)
    {
        if ($event instanceof NotificationSending ) {
            $this->sending($event);
        }
        elseif ($event instanceof NotificationSent ) {
            $this->sent($event);
        }
    }

    /**
     * @param NotificationSending $event
     */
    public function sending(NotificationSending $event)
    {
        $this->laritor->pushEvent(static::$eventType, [
            'id' => $event->notification->id,
            'notification' => get_class($event->notification),
            'notifiable' => $this->formatNotifiable($event->notifiable),
            'context' => $this->laritor->getContext(),
            'started_at' => now()->format('Y-m-d H:i:s'),
            'completed_at' => null
        ] );
    }

    /**
     * @param $notifiable
     * @return string
     */
    private function formatNotifiable($notifiable)
    {
        if ($notifiable instanceof Model) {
            return get_class($notifiable) .'@'.$notifiable->getKey();
        }

        if ($notifiable instanceof AnonymousNotifiable) {
            $routes = array_map(function ($route) {
                return is_array($route) ? implode(',', $route) : $route;
            }, $notifiable->routes);

            return DataHelper::redactData('Anonymous: '.implode(',', $routes));
        }

        return '';
    }

    /**
     * @param NotificationSent $event
     */
    public function sent(NotificationSent $event)
    {
        $id = $event->notification->id;

        $events = collect($this->laritor->getEvents(static::$eventType))->map(function ($event) use ($id){
            if ($event['id'] === $id) {
                $event['completed_at'] = now()->format('Y-m-d H:i:s');
            }
            return $event;
        });

        $this->laritor->addEvents(static::$eventType, $events);
    }
}
