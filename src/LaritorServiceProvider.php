<?php

namespace Laritor\LaravelClient;

use Illuminate\Routing\ControllerDispatcher;
use Illuminate\Routing\Events\PreparingResponse;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laritor\LaravelClient\Commands\DiscoverCommand;
use Laritor\LaravelClient\Commands\HealthCheckMakeCommand;

class LaritorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->mergeConfigFrom( __DIR__ . '/../config/laritor.php', 'laritor' );

        if ( ! config('laritor.enabled') ) {
            return;
        }

        if (method_exists($this->app, 'scoped')) {
            $this->app->scoped(Laritor::class, function () {
                return new Laritor();
            });
        } else {
            $this->app->singleton(Laritor::class);
        }

        $this->registerRecorders();

        $this->commands([
            HealthCheckMakeCommand::class,
            DiscoverCommand::class
        ]);

        app(Laritor::class)->setStarted();

        app()->bind(ControllerDispatcher::class, function ($app) {
            return new class($app) extends ControllerDispatcher {
                public function dispatch($route, $controller, $method)
                {
                    app(Laritor::class)->setMiddlewareEnded();
                    return parent::dispatch($route, $controller, $method);
                }
            };
        });

        Event::listen(function (PreparingResponse $event) {
            app(Laritor::class)->setControllerCompleted();
        });

        Event::listen(function (ResponsePrepared $event) {
            app(Laritor::class)->setResponseCompleted();
        });


        $this->app->booted(function () {
            $this->routes();

            app(Laritor::class)->setBooted();

        });
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

        Event::listen( [ \Illuminate\Queue\Events\JobFailed::class ], function (){
            app(Laritor::class)->sendEvents();
        });

        $this->app->terminating(function (){
            app(Laritor::class)->sendEvents();
        });
    }
}
