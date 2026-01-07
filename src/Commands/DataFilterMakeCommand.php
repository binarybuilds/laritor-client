<?php

namespace BinaryBuilds\LaritorClient\Commands;

use Illuminate\Console\GeneratorCommand;

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
       return __DIR__.'/../../stubs/LaritorDataFilter.stub';
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
        return [];
    }

    protected function getNameInput()
    {
        return 'LaritorDataFilter';
    }
}
