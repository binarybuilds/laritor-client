<?php

namespace Laritor\LaravelClient\Helpers;

use Illuminate\Support\Str;
use Laritor\LaravelClient\Recorders\ScheduledTaskRecorder;

/**
 * Class ScheduledTaskHelper
 * @package Laritor\LaravelClient\Helpers
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

        if (in_array(ScheduledTaskRecorder::class, config('laritor.recorders'))) {
            foreach (app()->make(\Illuminate\Console\Scheduling\Schedule::class)->events() as $event) {
                $scheduled_tasks[] = [
                    'task' => Str::substr(
                        Str::replace("'",'', $event->command),
                        Str::position(Str::replace("'",'', $event->command), 'artisan')
                    ),
                    'expression' => $event->expression
                ];
            }
        }

        return $scheduled_tasks;
    }
}