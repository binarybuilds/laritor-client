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

            if (!is_array($request->data())) {
                echo "direct json\n";
                echo $request->data();
                echo "\n";
            } else {
                echo "encoded json\n";
                echo json_encode(
                    $request->data(),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
                );
                echo "\n";
            }

            $data = is_array($request->data()) ? json_encode(
                $request->data(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
            ) : $request->data();

            file_put_contents($path, $data);

            return Http::response(['success' => true], 200);
        });

        $app['router']->get('/test', function () {

            return response('OK', 200);
        });

        $app['router']->get('/query-test', function () {
            \Illuminate\Support\Facades\DB::select('SELECT 1 as ok');

            return response('OK', 200);
        });

        $app['router']->get('/external-http-test', function () {
            \Illuminate\Support\Facades\Http::get('https://example.com');

            return response('OK', 200);
        });

        $app['router']->get('/mail-test', function () {
            \Illuminate\Support\Facades\Mail::raw('Test mail body', function ($msg) {
                $msg->to('test@example.com')->subject('Test');
            });

            return response('OK', 200);
        });

        $app['router']->get('/notification-test', function () {
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

        $app['router']->get('/log-test', function () {
            \Illuminate\Support\Facades\Log::info('This is a test log');

            return response('OK', 200);
        });

        $app['router']->get('/cache-test', function () {
            \Illuminate\Support\Facades\Cache::put('foo', 'bar', 10);
            \Illuminate\Support\Facades\Cache::get('foo');

            return response('OK', 200);
        });

        $app['router']->get('/exception-test', function () {
            throw new \RuntimeException('Test exception');
        });

        $app['router']->get('/job-test', function () {
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
