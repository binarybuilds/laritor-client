<?php

namespace Laritor\LaravelClient\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Laritor\LaravelClient\Laritor;
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
        $scheduled_commands = [];

        foreach (app()->make(\Illuminate\Console\Scheduling\Schedule::class)->events() as $event) {
            $scheduled_commands[] = [
                'command' => Str::substr(
                    Str::replace("'",'', $event->command),
                    Str::position(Str::replace("'",'', $event->command), 'artisan')
                ),
                'expression' => $event->expression
            ];
        }

        $health_checks = [];

        if (is_dir(app_path('Laritor'))) {
            $namespace = app()->getNamespace();
            foreach ((new Finder)->in(app_path('Laritor'))->files() as $health_check) {
                $health_checks[] = $namespace.str_replace(
                        ['/', '.php'],
                        ['\\', ''],
                        Str::after($health_check->getPathname(), app_path().DIRECTORY_SEPARATOR)
                    );
            }
        }

        app()
            ->make(Laritor::class)
            ->discover($health_checks, $scheduled_commands);
    }
}
