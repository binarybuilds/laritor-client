<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;

/**
 * Class CommandRecorder
 * @package Laritor\LaravelClient\Recorders
 */
class CommandRecorder extends Recorder
{
    /**
     * @var string
     */
    public static $eventType = 'commands';

    /**
     * @var string[]
     */
    public static $events = [
        CommandStarting::class,
        CommandFinished::class
    ];

    /**
     * @param $event
     */
    public function trackEvent($event)
    {
        if ($this->ignore($event->command)) {
            return;
        }

        if ($event instanceof CommandStarting ) {
            $this->start($event);
        } elseif ($event instanceof CommandFinished ) {
            $this->finish($event);
        }
    }

    /**
     * @param CommandStarting $event
     */
    public function start(CommandStarting $event)
    {
        $arguments = array_filter(
            array_map(function ($option){
                if (is_array($option)) {
                    return implode(',', $option);
                }
                return $option;
            }, $event->input->getArguments()
            )
        );

        $arguments = implode(' ',  $arguments);

        $options = array_filter(
            array_map(function ($option){
                if (is_array($option)) {
                    return implode(',', $option);
                }
                return $option;
            }, $event->input->getOptions()
            )
        );

        $options = implode(' ',  $options);

        $this->laritor->pushEvent(static::$eventType,  [
            'command' => trim($arguments.' '.$options),
            'started_at' => now(),
            'completed_at' => null
        ]);
    }

    /**
     * @param CommandFinished $event
     */
    public function finish(CommandFinished $event)
    {
        $command = $this->laritor->getEvents(static::$eventType);
        $command = isset($command[0]) ? $command[0] : null;

        if ($command) {
            $startTime = defined('LARAVEL_START') ? LARAVEL_START : 0;
            $duration =  $startTime ? floor((microtime(true) - $startTime) * 1000) : 0;

            $command['duration'] = $duration;
            $command['completed_at'] = now()->format('Y-m-d H:i:s');
            $command['started_at'] = $command['started_at']->format('Y-m-d H:i:s');
            $command['code'] = $event->exitCode;

            $this->laritor->addEvents(static::$eventType, [$command]);
        }
    }

    /**
     * @param $command
     * @return bool
     */
    public function ignore($command)
    {
        return in_array($command, [
            'schedule:run',
            'schedule:finish',
            'package:discover',
            'event:cache',
            'view:cache',
            'config:cache',
            'queue:work',
            'queue:listen',
            'laritor:sync',
            'laritor:send-metrics'
        ]);
    }
}
