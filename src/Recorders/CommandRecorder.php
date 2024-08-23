<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;

class CommandRecorder extends Recorder
{
    public static $events = [
        CommandStarting::class,
        CommandFinished::class
    ];

    /**
     * @param $event
     * @return void
     */
    public function trackEvent($event)
    {
        if ($event instanceof CommandStarting ) {
            $this->start($event);
        } elseif ($event instanceof CommandFinished ) {
            $this->finish($event);
        }
    }

    /**
     * @return void
     */
    public function start(CommandStarting $event)
    {
        $this->laritor->pushEvent('commands',  [
            'command' => $event->command .' '
                . implode(' ', $event->input->getArguments()).' '
                .implode(' ',  $event->input->getOptions()),
            'started_at' => now(),
            'completed_at' => null
        ]);
    }

    /**
     * @return void
     */
    public function finish(CommandFinished $event)
    {
        $command = $this->laritor->getEvents('commands');
        $command = isset($command[0]) ? $command[0] : null;

        if ($command) {
            $command['duration'] = now()->diffInSeconds($command['started_at']);
            $command['completed_at'] = now()->toDateTimeString();
            $command['started_at'] = $command['started_at']->toDateTimeString();
            $command['code'] = $event->exitCode;

            $this->laritor->addEvents('commands', [$command]);
        }
    }
}
