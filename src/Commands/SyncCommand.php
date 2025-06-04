<?php

namespace BinaryBuilds\LaritorClient\Commands;

use Illuminate\Console\Command;
use BinaryBuilds\LaritorClient\Helpers\DatabaseHelper;
use BinaryBuilds\LaritorClient\Helpers\HealthCheckHelper;
use BinaryBuilds\LaritorClient\Helpers\ScheduledTaskHelper;
use BinaryBuilds\LaritorClient\Laritor;
use BinaryBuilds\LaritorClient\Recorders\DatabaseSchemaRecorder;
use BinaryBuilds\LaritorClient\Recorders\ScheduledTaskRecorder;

class SyncCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laritor:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync data with laritor';

    /**
     * @param ScheduledTaskHelper $scheduledTaskHelper
     * @param HealthCheckHelper $healthCheckHelper
     * @param DatabaseHelper $databaseHelper
     * @param Laritor $laritor
     * @return int
     * @throws \Illuminate\Contracts\Container\BindingResolutionException
     */
    public function handle(
        ScheduledTaskHelper $scheduledTaskHelper,
        HealthCheckHelper $healthCheckHelper,
        DatabaseHelper $databaseHelper,
        Laritor $laritor
    )
    {
        $scheduled_tasks = [];
        if (in_array(ScheduledTaskRecorder::class, config('laritor.recorders'))) {
            $scheduled_tasks = $scheduledTaskHelper->getScheduledTasks();
        }

        $health_checks = $healthCheckHelper->getHealthChecks();

        $schema = [];
        if (in_array(DatabaseSchemaRecorder::class, config('laritor.recorders'))) {
            $schema = $databaseHelper->getSchema();
        }

        $laritor->sync([
            'scheduled_tasks' => $scheduled_tasks,
            'health_checks' => $health_checks,
            'db_schema' => $schema
        ]);

        return self::SUCCESS;
    }
}
