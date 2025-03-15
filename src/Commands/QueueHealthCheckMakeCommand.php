<?php

namespace Laritor\LaravelClient\Commands;

use Illuminate\Console\GeneratorCommand;

class QueueHealthCheckMakeCommand extends GeneratorCommand
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

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
       return __DIR__.'/../../stubs/LaritorHealthCheck.stub';
    }

    /**
     * Get the default namespace for the class.
     *
     * @param  string  $rootNamespace
     * @return string
     */
    protected function getDefaultNamespace($rootNamespace)
    {
        return $rootNamespace.'\Laritor';
    }
}
