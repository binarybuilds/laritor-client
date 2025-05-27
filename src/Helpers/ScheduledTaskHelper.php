<?php

namespace BinaryBuilds\LaritorClient\Helpers;

use Illuminate\Support\Str;
use BinaryBuilds\LaritorClient\Recorders\ScheduledTaskRecorder;

/**
 * Class ScheduledTaskHelper
 * @package BinaryBuilds\LaritorClient\Helpers
 */
class ScheduledTaskHelper
{
    /**
     * @return array
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function getScheduledTasks()
    {
        $scheduled_tasks = [];

        foreach (app()->make(\Illuminate\Console\Scheduling\Schedule::class)->events() as $event) {
            $task = Str::substr(
                Str::replace("'",'', $event->command),
                Str::position(Str::replace("'",'', $event->command), 'artisan')
            );

            if (in_array($task, ['artisan laritor:send-metrics'])) {
                continue;
            }

            $scheduled_tasks[] = [
                'task' => $task,
                'expression' => $event->expression
            ];
        }

        return $scheduled_tasks;
    }
}