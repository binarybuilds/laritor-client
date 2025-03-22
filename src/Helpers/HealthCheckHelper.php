<?php

namespace Laritor\LaravelClient\Helpers;

use Illuminate\Support\Str;
use Laritor\LaravelClient\Recorders\SchedulerRecorder;
use Symfony\Component\Finder\Finder;

/**
 * Class ScheduledTaskHelper
 * @package Laritor\LaravelClient\Helpers
 */
class HealthCheckHelper
{
    /**
     * @return array
     */
    public function getHealthChecks()
    {
        $health_checks = [];

        $health_checks[] = [
            'name' => 'Task Scheduler',
            'type' => 'scheduler',
            'expression' => '* * * * *',
            'timeout' => 10,
            'active' => in_array(SchedulerRecorder::class, config('laritor.recorders'))
        ];

        if (is_dir(app_path('Laritor'))) {
            foreach ((new Finder())->in(app_path('Laritor'))->files() as $health_check) {
                $name = str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($health_check->getPathname(), app_path('Laritor').DIRECTORY_SEPARATOR)
                );

                $class = app()->getNamespace().'Laritor\\'.$name;

                $health_checks[] = [
                    'name' => $class::$name,
                    'type' => $name,
                    'expression' => $class::$expression,
                    'timeout' => $class::$timeout,
                    'active' => $class::isActive()
                ];
            }
        }

        return $health_checks;
    }
}