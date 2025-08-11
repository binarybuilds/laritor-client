<?php

namespace BinaryBuilds\LaritorClient;

use BinaryBuilds\LaritorClient\Redactor\DataRedactor;
use BinaryBuilds\LaritorClient\Redactor\DefaultRedactor;
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
        $this->publishes([
            __DIR__ . '/../config/laritor.php' => config_path('laritor.php'),
        ]);

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
        $this->app->bind(DataRedactor::class, DefaultRedactor::class);
    }

    protected function routes()
    {
        if ($this->app->routesAreCached()) {
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
        foreach ((array)config('laritor.recorders') as $recorder) {
            $recorder::registerRecorder();
        }

        if (class_exists(\Laravel\Octane\Events\RequestReceived::class)) {
            Event::listen( [
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
