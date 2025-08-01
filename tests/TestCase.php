<?php

namespace BinaryBuilds\LaritorClient\Tests;

use Illuminate\Support\Facades\Http;
use Orchestra\Testbench\TestCase as Orchestra;
use BinaryBuilds\LaritorClient\LaritorServiceProvider;
use Illuminate\Support\Facades\File;


abstract class TestCase extends Orchestra
{
    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            LaritorServiceProvider::class,
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $path = __DIR__.'/payloads/events.json';

        if (File::exists($path)) {
            File::delete($path);
        }

        Http::fake(function ($request, $options) use ($path) {

            if (!File::exists(dirname($path))) {
                File::makeDirectory(dirname($path), 0755, true);
            }

            file_put_contents($path, $request->body());

            return Http::response(['success' => true], 200);
        });

        $app['router']->get('/laritor-test', function () {

            return response('OK', 200);
        });

        $app['router']->get('/laritor-query', function () {
            \Illuminate\Support\Facades\DB::select('SELECT 1 as ok');

            return response('OK', 200);
        });

        $app['router']->get('/laritor-external-http', function () {
            \Illuminate\Support\Facades\Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'custom-header' => 'hello'
            ])->post('https://example.com', [
                'hello' => 'world',
            ]);

            return response('OK', 200);
        });

        $app['router']->get('/laritor-mail', function () {
            \Illuminate\Support\Facades\Mail::raw('Test mail body', function ($msg) {
                $msg->to('test@example.com')->subject('Test');
            });

            return response('OK', 200);
        });

        $app['router']->get('/laritor-notification', function () {
            \Illuminate\Support\Facades\Notification::route('mail', 'test@example.com')
                ->notify(new class extends \Illuminate\Notifications\Notification {
                    public function via($notifiable) { return ['mail']; }
                    public function toMail($notifiable) {
                        return (new \Illuminate\Notifications\Messages\MailMessage)
                            ->line('Hello');
                    }
                });

            return response('OK', 200);
        });

        $app['router']->get('/laritor-log', function () {
            \Illuminate\Support\Facades\Log::info('This is a test log 378282246310005', [
                'Authorization' => 'sensitive key'
            ]);

            return response('OK', 200);
        });

        $app['router']->get('/laritor-cache', function () {
            \Illuminate\Support\Facades\Cache::put('foo', 'bar', 10);
            \Illuminate\Support\Facades\Cache::get('foo');

            return response('OK', 200);
        });

        $app['router']->get('/laritor-exception', function () {
            throw new \RuntimeException('Test exception');
        });

        $app['router']->get('/laritor-job', function () {
            dispatch(function (){
                return 'OK';
            });
            return response('OK', 200);
        });
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate:fresh', [
            '--path' => realpath(__DIR__ . '/../database/migrations'),
            '--database' => config('database.default'),
        ])->run();
    }
}
