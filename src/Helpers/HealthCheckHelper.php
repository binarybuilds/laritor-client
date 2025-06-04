<?php

namespace BinaryBuilds\LaritorClient\Helpers;

use Illuminate\Support\Str;
use BinaryBuilds\LaritorClient\Recorders\SchedulerRecorder;
use Symfony\Component\Finder\Finder;

/**
 * Class ScheduledTaskHelper
 * @package BinaryBuilds\LaritorClient\Helpers
 */
class HealthCheckHelper
{
    /**
     * @return array
     */
    public function getHealthChecks()
    {
        $health_checks = [];

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
                    'timeout' => $class::$timeout
                ];
            }
        }

        return $health_checks;
    }
}