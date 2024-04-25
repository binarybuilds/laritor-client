<?php

namespace Laritor\LaravelClient;

use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\Events\KeyForgotten;
use Illuminate\Cache\Events\KeyWritten;
use Illuminate\Console\Events\CommandFinished;
use Illuminate\Console\Events\CommandStarting;
use Illuminate\Console\Events\ScheduledTaskFinished;
use Illuminate\Console\Events\ScheduledTaskStarting;
use Illuminate\Container\Container;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Queue\Events\JobQueued;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Laritor\LaravelClient\Recorders\CommandRecorder;
use Laritor\LaravelClient\Recorders\ExceptionRecorder;
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
    }

    public function registerRecorders()
    {
        Event::listen( MessageLogged::class, [ExceptionRecorder::class, 'handle'] );
        Event::listen( RequestHandled::class, [RequestRecorder::class, 'handle'] );
        Event::listen( QueryExecuted::class, [QueryRecorder::class, 'handle'] );
        Event::listen( CommandStarting::class, [SchedulerRecorder::class, 'start'] );
        Event::listen( CommandFinished::class, [SchedulerRecorder::class, 'finish'] );
        Event::listen( ScheduledTaskStarting::class, [ScheduledCommandRecorder::class, 'start'] );
        Event::listen( ScheduledTaskFinished::class, [ScheduledCommandRecorder::class, 'finish'] );
        Event::listen( JobQueued::class, [QueuedJobRecorder::class, 'queued'] );
        Event::listen( JobProcessing::class, [QueuedJobRecorder::class, 'started'] );
        Event::listen( JobProcessed::class, [QueuedJobRecorder::class, 'processed'] );
        Event::listen( JobFailed::class, [QueuedJobRecorder::class, 'failed'] );


//        Event::listen( CacheMissed::class, [QueryRecorder::class, 'handle'] );
//        Event::listen( KeyWritten::class, [QueryRecorder::class, 'handle'] );
//        Event::listen( KeyForgotten::class, [QueryRecorder::class, 'handle'] );

        $this->app->terminating(function (){
            app(Laritor::class)->sendEvents();
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}
