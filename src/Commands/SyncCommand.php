<?php

namespace BinaryBuilds\LaritorClient\Commands;

use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use BinaryBuilds\LaritorClient\SendOutputToLaritor;
use Illuminate\Console\Command;
use BinaryBuilds\LaritorClient\Helpers\DatabaseHelper;
use BinaryBuilds\LaritorClient\Helpers\HealthCheckHelper;
use BinaryBuilds\LaritorClient\Helpers\ScheduledTaskHelper;
use BinaryBuilds\LaritorClient\Laritor;

class SyncCommand extends Command
{
    use SendOutputToLaritor;

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
        if (! config('laritor.enabled') ) {
            $this->warn('Laritor not enabled');
            return self::SUCCESS;
        }

        if (! config('laritor.keys.backend') ) {
            $this->error('Laritor key is not configured. Please add the LARITOR_BACKEND_KEY env variable');
            return self::FAILURE;
        }

        if (! config('laritor.ingest_endpoint') ) {
            $this->error('Laritor ingest endpoint is not configured. Please add the LARITOR_INGEST_ENDPOINT env variable');
            return self::FAILURE;
        }

        $scheduled_tasks = $scheduledTaskHelper->getScheduledTasks();;

        $health_checks = $healthCheckHelper->getHealthChecks();

        $schema = FilterHelper::recordDatabaseSchema() ? $databaseHelper->getSchema() : [];

        $response = $laritor->sync([
            'scheduled_tasks' => $scheduled_tasks,
            'health_checks' => $health_checks,
            'db_schema' => $schema
        ]);

        if ($response['success']) {
            $this->info('Laritor successfully synced');
        } else {
            $this->error('Laritor sync failed with error: ' . $response['message']);
        }

        return self::SUCCESS;
    }
}
