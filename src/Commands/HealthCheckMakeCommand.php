<?php

namespace Laritor\LaravelClient\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Support\Str;

class HealthCheckMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:laritor-custom-hc';

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

    /**
     * @param string $stub
     * @param string $name
     * @return array|string|string[]
     */
    protected function replaceClass($stub, $name)
    {
        $stub = parent::replaceClass($stub, $name);

        return str_replace(['{{ name }}'], Str::headline($name), $stub);
    }
}
