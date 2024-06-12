<?php

namespace Laritor\LaravelClient\Commands;

use Illuminate\Console\GeneratorCommand;

class HealthCheckMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laritor:make-hc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a custom health check for laritor';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'HealthCheck';

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
