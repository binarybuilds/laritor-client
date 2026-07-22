<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\CommandOutput;
use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Support\Str;

/**
 * Class CommandRecorder
 * @package BinaryBuilds\LaritorClient\Recorders
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

        $commandString = $arguments.' ';
        foreach ($options as $option => $value) {
            $commandString .= '--'.$option.'='.$value.' ';
        }

        $this->laritor->pushEvent(static::$eventType,  [
            'command' => trim($commandString),
            'started_at' => now(),
            'completed_at' => null
        ]);
    }

    /**
     * @param CommandFinished $event
     */
    public function finish(CommandFinished $event)
    {
        $command = collect(
            $this->laritor->getEvents(static::$eventType)
        )->firstWhere('completed_at', '=',null);

        if ($command) {
            $duration = $command['started_at']->diffInMilliseconds();
            $this->laritor->setCommandDuration($duration);
            if ($event->exitCode > 0) {
                $this->laritor->setFailedCommand($event->command);
            }

            $command['duration'] = $duration;
            $command['completed_at'] = now()->format('Y-m-d H:i:s');
            $command['started_at'] = $command['started_at']->format('Y-m-d H:i:s');
            $command['code'] = $event->exitCode;
            $command['custom_context'] = FilterHelper::recordCommandContext($event->command, $event->exitCode > 0 ? 'failed' : 'completed', $duration) ? DataHelper::getRedactedContext() : [];
            $command['output'] = app(CommandOutput::class)->getLines();

            app(CommandOutput::class)->resetLines();

            $this->laritor->addEvents(static::$eventType, [$command]);
        }
    }

    /**
     * @param $command
     * @return bool
     */
    public function ignore($command)
    {
        return Str::startsWith($command, [
            'horizon',
            'pulse:',
            'db:seed',
            'optimize',
            'schedule:work',
            'schedule:run',
            'schedule:finish',
            'package:discover',
            'event:cache',
            'view:cache',
            'config:cache',
            'queue:work',
            'queue:listen',
            'octane:install',
            'auth:clear-resets',
            'config:cache',
            'horizon:snapshot',
            'horizon:status',
            'horizon:supervisor',
            'inertia:start-ssr',
            'invoke-serialized-closure',
            'model:prune',
            'nightwatch:agent',
            'nightwatch:status',
            'queue:monitor',
            'reverb:start',
            'schedule:list',
            'laritor:sync',
            'laritor:send-metrics',
            'vendor:publish',
        ]);
    }
}
