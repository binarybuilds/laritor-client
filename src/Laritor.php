<?php

namespace BinaryBuilds\LaritorClient;

use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use BinaryBuilds\LaritorClient\Recorders\CacheRecorder;
use BinaryBuilds\LaritorClient\Recorders\CommandRecorder;
use BinaryBuilds\LaritorClient\Recorders\ExceptionRecorder;
use BinaryBuilds\LaritorClient\Recorders\FeatureFlagRecorder;
use BinaryBuilds\LaritorClient\Recorders\LogRecorder;
use BinaryBuilds\LaritorClient\Recorders\MailRecorder;
use BinaryBuilds\LaritorClient\Recorders\NotificationRecorder;
use BinaryBuilds\LaritorClient\Recorders\OutboundRequestRecorder;
use BinaryBuilds\LaritorClient\Recorders\QueryRecorder;
use BinaryBuilds\LaritorClient\Recorders\QueuedJobRecorder;
use BinaryBuilds\LaritorClient\Recorders\RequestRecorder;
use BinaryBuilds\LaritorClient\Recorders\ScheduledTaskRecorder;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use BinaryBuilds\LaritorClient\Recorders\SchedulerRecorder;

class Laritor
{
    public const VERSION = '4.0.0';

    /**
     * @var array
     */
    private $events = [];

    private $order = 1;

    private $eventsCount = 0;

    private $started = 0;

    private $booted = 0;

    private $middleware = 0;

    private $controller = 0;

    private $response = 0;

    private $context = 'BOOT';

    private $hasCustomLogs = false;

    public const CUSTOM_EVENT = 'custom';

    private $exception = null;

    private int $requestStatus = 0;

    private int $requestDuration = 0;

    private $failedJob = null;

    private $failedCommand = null;

    private $jobDuration = 0;

    private $commandDuration = 0;

    public function getJobDuration()
    {
        return $this->jobDuration;
    }

    public function setJobDuration($jobDuration): void
    {
        $this->jobDuration = $jobDuration;
    }

    public function getCommandDuration()
    {
        return $this->commandDuration;
    }

    public function setCommandDuration($commandDuration): void
    {
        $this->commandDuration = $commandDuration;
    }

    /**
     * @param $failedJob
     * @return void
     */
    public function setFailedJob($failedJob): void
    {
        $this->failedJob = $failedJob;
    }

    /**
     * @param $failedCommand
     * @return void
     */
    public function setFailedCommand($failedCommand): void
    {
        $this->failedCommand = $failedCommand;
    }

    public function hasFailedJob(): bool
    {
        return !is_null($this->failedJob);
    }

    public function hasFailedCommand(): bool
    {
        return !is_null($this->failedCommand);
    }

    /**
     * @param int $status
     * @return void
     */
    public function setRequestStatus(int $status): void
    {
        $this->requestStatus = $status;
    }

    /**
     * @param int $duration
     * @return void
     */
    public function setRequestDuration(int $duration): void
    {
        $this->requestDuration = $duration;
    }

    public function getRequestStatus(): int
    {
        return $this->requestStatus;
    }

    public function getRequestDuration(): int
    {
        return $this->requestDuration;
    }

    /**
     * @return \Throwable|null
     */
    public function getException()
    {
        return $this->exception;
    }

    public static function hasException(): bool
    {
        return !is_null(app(Laritor::class)->getException());
    }

    /**
     * @param \Throwable|null $exception
     */
    public function setException($exception): void
    {
        $this->exception = $exception;
    }

    /**
     * @return string
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param string $context
     */
    public function setContext($context)
    {
        $this->context = $context;
    }

    public function started()
    {
        $this->started = defined('LARAVEL_START') ? LARAVEL_START : request()->server('REQUEST_TIME_FLOAT');

        if (!$this->started) {
            $this->started = microtime(true);
        }
    }

    public function octaneRequestStarted()
    {
        $this->reset();
        $this->context = 'MIDDLEWARE';
        $this->started = microtime(true);
    }

    public function booted()
    {
        $this->booted = $this->started ? $this->getDurationFrom($this->started) : 0;

        if ( App::runningInConsole() ) {
            $this->setContext('COMMAND');
        } else {
            $this->setContext('MIDDLEWARE');
        }
    }

    public function controllerStarted()
    {
        if (!$this->started) {
            $this->started = microtime(true);
        }

        $this->middleware = $this->getDurationFrom($this->started) - $this->booted;
        $this->setContext('CONTROLLER');
    }

    public function responseRenderStarted()
    {
        if ($this->context !== 'RESPONSE') {
            $this->controller = $this->getDurationFrom($this->started) - ($this->booted + $this->middleware );
            $this->setContext('RESPONSE');
        }
    }

    public function responseRenderCompleted($exception)
    {
        if ($exception) {
            switch ($this->getContext()) {
                case 'BOOT': $this->booted = $this->getDurationFrom($this->started);break;
                case 'MIDDLEWARE': $this->middleware = $this->getDurationFrom($this->started) - $this->booted;break;
                case 'CONTROLLER': $this->controller = $this->getDurationFrom($this->started) - ($this->booted + $this->middleware);break;
                default: $this->response = $this->getDurationFrom($this->started) - (
                        $this->booted + $this->middleware + $this->controller
                    );break;
            }
        } else {
            $this->response = $this->getDurationFrom($this->started) - (
                $this->booted + $this->middleware + $this->controller
                );
        }
    }

    public function getDurationFrom($time)
    {
        return floor((microtime(true) - $time) * 1000);
    }

    public function getDurationFromStart()
    {
        return $this->getDurationFrom($this->started);
    }

    /**
     * @param $name
     * @param $event
     * @return $this
     */
    public function pushEvent($name, $event)
    {
        if ($this->eventsCount <= config('laritor.max_events')) {
            $event['order'] = $this->order;
            $event['timestamp'] = microtime(true);
            $this->order++;
            $this->events[ $name ][] = $event;
            $this->eventsCount++;
        }

        return $this;
    }

    /**
     * @param $name
     * @param $events
     * @return $this
     */
    public function addEvents($name, $events)
    {
        $this->events[$name] = $events;
        return $this;
    }

    public function addCustomLog(string $type, string $level, string $message, array $context = [], ?Carbon $written_at = null)
    {
        $this->hasCustomLogs = true;
        $this->pushEvent(LogRecorder::$eventType, [
            'level' => $level,
            'message' => $message,
            'log_context' => $context,
            'occurred_at' => $written_at ? $written_at->format('Y-m-d H:i:s') : now()->format('Y-m-d H:i:s'),
            'context' => $type
        ]);
    }

    public static function addCustomEvent($name, $meta = [])
    {
        $laritor = app(Laritor::class);
        $meta = is_array($meta) ? $meta : [];
        $meta['name'] = $name;
        $meta['context'] = $laritor->getContext();
        $laritor->pushEvent(Laritor::CUSTOM_EVENT, $meta);
    }

    public function removeScheduler()
    {
        unset($this->events[SchedulerRecorder::$eventType]);
    }

    /**
     * @param $name
     * @return array|mixed
     */
    public function getEvents($name = null)
    {
        if ($name) {
            return isset($this->events[$name]) ? $this->events[$name] : [];
        }

        return $this->events;
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'app' => url('/'),
            'env' => !empty(config('laritor.env')) ? config('laritor.env') : config('app.env'),
            'event_at' => now()->toDateTimeString(),
            'version' => app()->version(),
            'php' => phpversion(),
            'client_version' => self::VERSION,
            'server' => [
                'host' => !empty(config('laritor.server_name')) ? config('laritor.server_name') : gethostname(),
            ],
            'events' => $this->events,
            'booted' => $this->booted,
            'middleware' => $this->middleware,
            'controller' => $this->controller,
            'response' => $this->response
        ];
    }

    /**
     * @return false|string
     */
    public function toJson()
    {
        return json_encode($this->toArray(), JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return void
     */
    public function reset()
    {
        $this->events = [];
        $this->order = 1;
        $this->eventsCount = 0;
        $this->started = 0;
        $this->booted = 0;
        $this->middleware = 0;
        $this->controller = 0;
        $this->response = 0;
        $this->context = 'BOOT';
        $this->hasCustomLogs = false;
        $this->exception = null;
        $this->requestStatus = 0;
        $this->requestDuration = 0;
        $this->failedJob = null;
        $this->failedCommand = null;
        $this->jobDuration = 0;
        $this->commandDuration = 0;
    }

    /**
     * @return void
     */
    public function sendEvents()
    {
        rescue(function () {
            Event::fakeFor(function (){
                $this->filterEvents();
                if ($this->shouldSendEvents()) {
                    $this->callApi();
                }

                $this->reset();
            });
        }, null, true);
    }

    public function filterEvents()
    {
        foreach ($this->events as $type => $events) {
            $filtered = [];
            foreach ($events as $event) {
                $shouldAdd = match ($type){
                    CacheRecorder::$eventType => FilterHelper::recordCacheHit($event['key']),
                    CommandRecorder::$eventType => FilterHelper::recordCommandOrScheduledTask($event['command'], $event['code'] === 0 ? 'completed' : 'failed', $event['duration'] ?? 0),
                    ExceptionRecorder::$eventType => FilterHelper::recordException($this->exception),
                    FeatureFlagRecorder::$eventType => FilterHelper::recordFeatureFlag($event['feature'], $event['feature_flag_scope']),
                    LogRecorder::$eventType => FilterHelper::recordLog($event['level'], $event['message'], $event['log_context']),
                    MailRecorder::$eventType => FilterHelper::recordMail($event['mailable'], $event['to'], $event['subject']),
                    NotificationRecorder::$eventType => FilterHelper::recordNotification($event['notifiable_instance'], $event['notification']),
                    OutboundRequestRecorder::$eventType => !empty($event['completed_at']) && FilterHelper::recordOutboundRequest($event['url'], $event['code'], $event['duration']),
                    QueryRecorder::$eventType => FilterHelper::recordQuery($event['query'], $event['time'], $event['path']),
                    QueuedJobRecorder::$eventType => FilterHelper::recordQueuedJob($event['connection'], $event['queue'], $event['job'], $event['status'], $event['duration'] ?? 0),
                    RequestRecorder::$eventType => FilterHelper::recordRequest($event['request_instance'], $event['response_instance'], $event['response']['status_code'], $event['request']['duration']),
                    ScheduledTaskRecorder::$eventType => FilterHelper::recordCommandOrScheduledTask($event['task'], $event['status'], $event['duration'] ?? 0),
                    'server_stats' => true,
                    default => false
                };

                if ($shouldAdd) {
                    unset($event['feature_flag_scope']);
                    unset($event['notifiable_instance']);
                    unset($event['request_instance']);
                    unset($event['response_instance']);

                    $filtered[] = $event;
                }
            }

            if (!empty($filtered)) {
                $this->events[$type] = $filtered;
            } else {
                unset($this->events[$type]);
            }
        }
    }

    /**
     * @return void
     */
    public function callApi()
    {
        $response = Http::withHeaders([
            'X-Api-Key' => config('laritor.keys.backend'),
            'Content-Type' => 'application/json',
        ])
            ->withUserAgent('laritor-client')
            ->withBody($this->toJson(), 'application/json')
            ->post(rtrim(config('laritor.ingest_endpoint'),'/').'/events');

        if ($response->status() === 429) {
            $timeout = (int)$response->header('Retry-After');
            file_put_contents(
                storage_path('laritor-timeout.txt'), now()->addSeconds($timeout)->toISOString()
            );
        }
    }

    /**
     * @param $data
     * @return array
     */
    public function sync($data)
    {
        try {
            $app = app();

            $data = json_encode([
                'env' => !empty(config('laritor.env')) ? config('laritor.env') : config('app.env'),
                'app' => url('/'),
                'version' => $app->version(),
                'php' => phpversion(),
                'client_version' => self::VERSION,
                'server' => [
                    'host' => !empty(config('laritor.server_name')) ? config('laritor.server_name') : gethostname(),
                    'os' => PHP_OS,
                ],
                'cache' => [
                    'config' => $app->configurationIsCached(),
                    'routes' => $app->routesAreCached(),
                    'events' => $app->eventsAreCached()
                ],
                'data' => $data
            ], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);

            $response = Http::withHeaders([
                'X-Api-Key' => config('laritor.keys.backend'),
                'Content-Type' => 'application/json',
            ])
                ->withUserAgent('laritor-client')
                ->withBody($data, 'application/json')
                ->post(rtrim(config('laritor.ingest_endpoint'),'/').'/sync');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'message' => 'sync successful!',
                ];
            }

            return [
                'success' => false,
                'message' => $response->body(),
            ];

        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * @return bool
     */
    public function shouldSendEvents()
    {
        if (empty($this->events)) {
            return false;
        }

        try {
            $timeout = trim(file_get_contents(storage_path('laritor-timeout.txt')));

            if (now()->parse($timeout)->isFuture()) {
                return false;
            }

        } catch (\Throwable $exception) {}

        $hasOccurrence = false;

        if ($this->hasCustomLogs || config('laritor.ingest_events_without_occurrence')) {
            $hasOccurrence = true;
        } else {
            foreach ($this->events as $type => $event) {
                if (in_array($type, ['requests', 'commands', 'scheduler', 'scheduled_tasks', 'jobs','server_stats'])) {
                    $hasOccurrence = true;
                    break;
                }
            }
        }

        return $hasOccurrence;
    }
}
