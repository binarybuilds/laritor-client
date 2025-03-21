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

        if (in_array(SchedulerRecorder::class, config('laritor.recorders'))) {
            $health_checks[] = 'Scheduler';
        }

        if (is_dir(app_path('Laritor'))) {
            $namespace = app()->getNamespace();
            foreach ((new Finder)->in(app_path('Laritor'))->files() as $health_check) {
                $health_checks[] = $namespace.str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        Str::after($health_check->getPathname(), app_path().DIRECTORY_SEPARATOR)
                    );
            }
        }

        return $health_checks;
    }
}