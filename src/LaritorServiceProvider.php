<?php

namespace Laritor\LaravelClient;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Http\Client\Events\ConnectionFailed;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Http\Client\Events\ResponseReceived;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laritor\LaravelClient\Commands\DiscoverCommand;
use Laritor\LaravelClient\Commands\HealthCheckMakeCommand;
use Laritor\LaravelClient\Recorders\CacheRecorder;
use Laritor\LaravelClient\Recorders\ExceptionRecorder;
use Laritor\LaravelClient\Recorders\OutboundRequestRecorder;
use Laritor\LaravelClient\Recorders\QueryRecorder;
use Laritor\LaravelClient\Recorders\QueuedJobRecorder;
use Laritor\LaravelClient\Recorders\RequestRecorder;
use Laritor\LaravelClient\Recorders\ScheduledCommandRecorder;
use Laritor\LaravelClient\Recorders\SchedulerRecorder;

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

        $this->app->booted(function () {
            $this->routes();
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
        $listeners = [
            MessageLogged::class => ExceptionRecorder::class,
            RequestHandled::class => RequestRecorder::class, 
            QueryExecuted::class => QueryRecorder::class,
            CommandStarting::class => SchedulerRecorder::class,
            CommandFinished::class => SchedulerRecorder::class,
            ScheduledTaskStarting::class => ScheduledCommandRecorder::class,
            ScheduledTaskFinished::class => ScheduledCommandRecorder::class,
            JobQueued::class => QueuedJobRecorder::class,
            JobProcessing::class => QueuedJobRecorder::class,
            JobProcessed::class => QueuedJobRecorder::class,
            JobFailed::class => QueuedJobRecorder::class,
            CacheHit::class => CacheRecorder::class,
            CacheMissed::class => CacheRecorder::class,
            RequestSending::class => OutboundRequestRecorder::class,
            ConnectionFailed::class => OutboundRequestRecorder::class,
            ResponseReceived::class => OutboundRequestRecorder::class,
        ];

        foreach ($listeners as $event => $listener ) {
            Event::listen( $event, [$listener, 'handle'] );
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
