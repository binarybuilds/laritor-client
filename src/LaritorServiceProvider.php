<?php

namespace BinaryBuilds\LaritorClient;

use BinaryBuilds\LaritorClient\Override\DefaultOverride;
use BinaryBuilds\LaritorClient\Override\LaritorOverride;
use BinaryBuilds\LaritorClient\Redactor\DataRedactor;
use BinaryBuilds\LaritorClient\Redactor\DefaultRedactor;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Contracts\CallableDispatcher;
use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Events\PreparingResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use BinaryBuilds\LaritorClient\Commands\SyncCommand;
use BinaryBuilds\LaritorClient\Commands\HealthCheckMakeCommand;
use BinaryBuilds\LaritorClient\Commands\QueueHealthCheckMakeCommand;
use BinaryBuilds\LaritorClient\Commands\SendServerMetricsCommand;
use Illuminate\Routing\Contracts\ControllerDispatcher as ControllerDispatcherContract;

/**
 * Class LaritorServiceProvider
 * @package BinaryBuilds\LaritorClient
 */
class LaritorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        if ( ! config('laritor.enabled') || !config('laritor.keys.backend') ) {
            return;
        }

        if (method_exists($this->app, 'scoped')) {
            $this->app->scoped(Laritor::class, function () {
                return new Laritor();
            });

            $this->app->scoped(CommandOutput::class, function () {
                return new CommandOutput();
            });
        } else {
            $this->app->singleton(Laritor::class);
            $this->app->singleton(CommandOutput::class);
        }

        $this->registerRecorders();

        $this->commands([
            HealthCheckMakeCommand::class,
            QueueHealthCheckMakeCommand::class,
            SyncCommand::class,
            SendServerMetricsCommand::class
        ]);

        app(Laritor::class)->started();

        app()->bind(ControllerDispatcherContract::class, function ($app) {
            return new class($app) extends ControllerDispatcher {
                public function dispatch($route, $controller, $method)
                {
                    app(Laritor::class)->controllerStarted();
                    return parent::dispatch($route, $controller, $method);
                }
            };
        });

        app()->bind(CallableDispatcher::class, function ($app) {
            return new class($app) extends \Illuminate\Routing\CallableDispatcher {
                public function dispatch($route, $callable)
                {
                    app(Laritor::class)->controllerStarted();
                    return parent::dispatch($route, $callable);
                }
            };
        });

        Event::listen(function (PreparingResponse $event) {
            app(Laritor::class)->responseRenderStarted();
        });


        $this->app->booted(function () {
            $this->routes();

            app(Laritor::class)->booted();

        });
    }

    public function register()
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/laritor.php', 'laritor');

        $this->app->bind(DataRedactor::class, DefaultRedactor::class);
        $this->app->bind(LaritorOverride::class, DefaultOverride::class);
    }

    protected function routes()
    {
        /**
         * @var Application $app
         */
        $app = $this->app;

        if ($app->routesAreCached()) {
            return;
        }

        Route::prefix('laritor')
            ->group( __DIR__ . '/Routes/laritor.php' );
    }

    /**
     * @return void
     */
    public function registerRecorders()
    {
        $recorders = [
            \BinaryBuilds\LaritorClient\Recorders\CacheRecorder::class,
            \BinaryBuilds\LaritorClient\Recorders\ExceptionRecorder::class,
            \BinaryBuilds\LaritorClient\Recorders\OutboundRequestRecorder::class,
            \BinaryBuilds\LaritorClient\Recorders\QueryRecorder::class,
            \BinaryBuilds\LaritorClient\Recorders\QueuedJobRecorder::class,
            \BinaryBuilds\LaritorClient\Recorders\RequestRecorder::class,
            \BinaryBuilds\LaritorClient\Recorders\CommandRecorder::class,
            \BinaryBuilds\LaritorClient\Recorders\ScheduledTaskRecorder::class,
            \BinaryBuilds\LaritorClient\Recorders\SchedulerRecorder::class,
            \BinaryBuilds\LaritorClient\Recorders\LogRecorder::class,
            \BinaryBuilds\LaritorClient\Recorders\MailRecorder::class,
            \BinaryBuilds\LaritorClient\Recorders\NotificationRecorder::class,
        ];

        foreach ($recorders as $recorder) {
            $recorder::registerRecorder();
        }

        if (
            class_exists(\Laravel\Octane\Events\RequestReceived::class) &&
            class_exists(\Laravel\Octane\Events\TaskReceived::class) &&
            class_exists(\Laravel\Octane\Events\TickReceived::class)
        ) {
            Event::listen([
                \Laravel\Octane\Events\RequestReceived::class,
                \Laravel\Octane\Events\TaskReceived::class,
                \Laravel\Octane\Events\TickReceived::class
            ], function (){
                app(Laritor::class)->sendEvents();
            } );
        }

        $this->app->terminating(function (){
            app(Laritor::class)->sendEvents();
        });
    }
}
