<?php

namespace BinaryBuilds\LaritorClient\Commands;

class QueueHealthCheckMakeCommand extends HealthCheckMakeCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:laritor-queue-hc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a queue worker health check for laritor';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'QueueWorkerHealthCheck';

    protected function getStub()
    {
        return __DIR__.'/../../stubs/QueueWorkerHealthCheck.stub';
    }
}
