<?php

namespace Laritor\LaravelClient\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

class DiscoverCommand extends Command
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'laritor:discover';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync custom health checks to laritor server';

    public function handle()
    {
        $namespace = app()->getNamespace();

        $health_checks = [];

        foreach ((new Finder)->in(app_path('Laritor'))->files() as $health_check) {
            $health_checks[] = $namespace.str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($health_check->getPathname(), app_path().DIRECTORY_SEPARATOR)
                );
        }

        //TODO: sync health checks to laritor
    }
}
