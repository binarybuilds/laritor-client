<?php

namespace BinaryBuilds\LaritorClient\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputArgument;

class DataFilterMakeCommand extends GeneratorCommand
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'make:laritor-filter';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a custom data filter for laritor';

    /**
     * The type of class being generated.
     *
     * @var string
     */
    protected $type = 'LaritorDataFilter';

    /**
     * Get the stub file for the generator.
     *
     * @return string
     */
    protected function getStub()
    {
        return match ($this->argument('type')) {
            'issues-only' => __DIR__.'/../../stubs/IssuesOnlyDataFilter.stub',
            'exceptions-only' => __DIR__.'/../../stubs/ExceptionsOnlyDataFilter.stub',
            default => __DIR__.'/../../stubs/FullObservabilityDataFilter.stub'
        };
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

    protected function getArguments()
    {
        return [
            ['type', InputArgument::OPTIONAL, 'The type of the filter', 'full-observability'],
        ];
    }

    protected function getNameInput()
    {
        return 'LaritorDataFilter';
    }
}
