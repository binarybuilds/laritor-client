<?php

namespace BinaryBuilds\LaritorClient\Helpers;

use BinaryBuilds\LaritorClient\Override\LaritorOverride;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

class FilterHelper
{
    public static $ignoredCommands = [
        'horizon', 'pulse:', 'db:seed', 'optimize', 'schedule:work', 'schedule:run',
        'schedule:finish', 'package:discover', 'event:cache', 'view:cache',
        'config:cache', 'queue:work', 'queue:listen', 'octane:install',
        'auth:clear-resets', 'config:cache', 'horizon:snapshot',
        'horizon:status', 'horizon:supervisor', 'inertia:start-ssr',
        'invoke-serialized-closure', 'model:prune', 'nightwatch:agent',
        'nightwatch:status', 'queue:monitor', 'reverb:start',
        'schedule:list', 'laritor:sync', 'laritor:send-metrics',
        'vendor:publish'
    ];

    public static function recordEvent(callable $callable, $default = true)
    {
        return rescue(function () use ($callable){
            return Event::fakeFor($callable);
        }, $default);
    }

    public static function recordCacheHit($cacheKey): bool
    {
        return ! Str::startsWith($cacheKey, ['laritor']) &&
            static::recordEvent(function () use ($cacheKey) {
            return app(LaritorOverride::class)->recordCacheHit($cacheKey);
        });
    }

    public static function recordException($exception): bool
    {
        return static::recordEvent(function () use ($exception) {
            return app(LaritorOverride::class)->recordException($exception);
        });
    }

    public static function recordOutboundRequest($url, $status_code, $duration): bool
    {
        return ! Str::contains($url, 'laritor.net') &&
            static::recordEvent(function () use ($url, $status_code, $duration) {
            return app(LaritorOverride::class)->recordOutboundRequest($url, $status_code, $duration);
        });
    }

    public static function recordQuery($query, $duration, $path): bool
    {
        return static::recordEvent(function () use ($query, $duration, $path) {
            return app(LaritorOverride::class)->recordQuery($query, $duration, $path);
        });
    }

    public static function recordQueuedJob(string $connection, string $queue, string $job, string $status, int $duration): bool
    {
        return ! Str::contains($job, 'QueueHealthCheck') &&
            static::recordEvent(function () use ($connection, $queue, $job, $status, $duration) {
                return app(LaritorOverride::class)->recordQueuedJob($connection, $queue, $job, $status, $duration);
        });
    }

    public static function recordRequest($request, $response, int $status, int $duration): bool
    {
        return !$request->is('laritor/*') && static::recordEvent(function () use ($request, $response, $status, $duration) {
            return app(LaritorOverride::class)->recordRequest($request, $response, $status, $duration, Auth::user());
        });
    }

    public static function recordCommandOrScheduledTask(string $command, string $status, int $duration): bool
    {
        return ! Str::contains($command, self::$ignoredCommands) &&
            static::recordEvent(function () use ($command, $status, $duration) {
            return app(LaritorOverride::class)->recordCommandOrScheduledTask($command, $status, $duration);
        });
    }

    public static function recordTaskScheduler(): bool
    {
        return static::recordEvent(function (){
            return app(LaritorOverride::class)->recordTaskScheduler();
        });
    }

    public static function recordMail($mailable, $to, $subject): bool
    {
        return static::recordEvent(function () use ($mailable, $to, $subject) {
            return app(LaritorOverride::class)->recordMail($mailable, $to, $subject);
        });
    }

    public static function recordNotification($notifiable, $notification): bool
    {
        return static::recordEvent(function () use ($notifiable, $notification) {
            return app(LaritorOverride::class)->recordNotification($notifiable, $notification);
        });
    }

    public static function recordFeatureFlag($flag, $scope): bool
    {
        return static::recordEvent(function () use ($flag, $scope) {
            return app(LaritorOverride::class)->recordFeatureFlag($flag, $scope);
        });
    }

    public static function recordLog($level, $message, array $context): bool
    {
        return static::recordEvent(function () use ($level, $message, $context) {
            return app(LaritorOverride::class)->recordLog($level, $message, $context);
        });
    }

    public static function isBot($request): bool
    {
        return static::recordEvent(function () use ($request) {
            return app(LaritorOverride::class)->isBot($request);
        }, false);
    }

    public static function recordCommandContext(string $command, string $status, int $duration): bool
    {
        return static::recordEvent(function () use ($command, $status, $duration) {
            return app(LaritorOverride::class)->recordCommandContext($command, $status, $duration);
        }, true);
    }

    public static function recordScheduledTaskContext(string $task, string $status, int $duration): bool
    {
        return static::recordEvent(function () use ($task, $status, $duration) {
            return app(LaritorOverride::class)->recordScheduledTaskContext($task, $status, $duration);
        }, true);
    }

    public static function recordRequestContext($request, $response, $status, $duration): bool
    {
        return static::recordEvent(function () use ($request, $response, $status, $duration) {
            return app(LaritorOverride::class)->recordRequestContext($request, $response, $status, $duration, Auth::user());
        }, true);
    }

    public static function recordLogContext($level, $message): bool
    {
        return static::recordEvent(function () use ($level, $message) {
            return app(LaritorOverride::class)->recordLogContext($level, $message);
        }, true);
    }

    public static function recordQueuedJobContext(string $connection, string $queue, string $job, string $status, int $duration): bool
    {
        return static::recordEvent(function () use ($connection, $queue, $job, $status, $duration) {
            return app(LaritorOverride::class)->recordQueuedJobContext($connection, $queue, $job, $status, $duration);
        }, true);
    }

    public static function recordDatabaseSchema(): bool
    {
        return static::recordEvent(function () {
            return app(LaritorOverride::class)->recordDatabaseSchema();
        }, true);
    }

    public static function recordQueryBindings($query, $duration, $path): bool
    {
        return static::recordEvent(function () use ($query, $duration, $path) {
            return app(LaritorOverride::class)->recordQueryBindings($query, $duration, $path);
        }, true);
    }

    public static function recordRequestQueryParameters($request, $response, $status, $duration): bool
    {
        return static::recordEvent(function () use ($request, $response, $status, $duration) {
            return app(LaritorOverride::class)->recordRequestQueryParameters($request, $response, $status, $duration, Auth::user());
        }, true);
    }

    public static function recordRequestHeaders($request, $response, $status, $duration): bool
    {
        return static::recordEvent(function () use ($request, $response, $status, $duration) {
            return app(LaritorOverride::class)->recordRequestHeaders($request, $response, $status, $duration, Auth::user());
        }, true);
    }

    public static function recordRequestBody($request, $response, $status, $duration): bool
    {
        return static::recordEvent(function () use ($request, $response, $status, $duration) {
            return app(LaritorOverride::class)->recordRequestBody($request, $response, $status, $duration, Auth::user());
        }, false);
    }

    public static function recordResponseHeaders($request, $response, $status, $duration): bool
    {
        return static::recordEvent(function () use ($request, $response, $status, $duration) {
            return app(LaritorOverride::class)->recordResponseHeaders($request, $response, $status, $duration, Auth::user());
        }, true);
    }

    public static function recordResponseBody($request, $response, $status, $duration): bool
    {
        return static::recordEvent(function () use ($request, $response, $status, $duration) {
            return app(LaritorOverride::class)->recordResponseBody($request, $response, $status, $duration, Auth::user());
        }, false);
    }

    public static function recordSessionData($request, $response, $status, $duration): bool
    {
        return static::recordEvent(function () use ($request, $response, $status, $duration) {
            return app(LaritorOverride::class)->recordSessionData($request, $response, $status, $duration, Auth::user());
        }, true);
    }

    public static function recordOutboundRequestHeaders($url, $status_code, $duration): bool
    {
        return static::recordEvent(function () use ($url, $status_code, $duration) {
            return app(LaritorOverride::class)->recordOutboundRequestHeaders($url, $status_code, $duration);
        }, true);
    }

    public static function recordOutboundRequestBody($url, $status_code, $duration): bool
    {
        return static::recordEvent(function () use ($url, $status_code, $duration) {
            return app(LaritorOverride::class)->recordOutboundRequestBody($url, $status_code, $duration);
        }, false);
    }

    public static function recordOutboundRequestResponseHeaders($url, $status_code, $duration): bool
    {
        return static::recordEvent(function () use ($url, $status_code, $duration) {
            return app(LaritorOverride::class)->recordOutboundRequestResponseHeaders($url, $status_code, $duration);
        }, true);
    }

    public static function recordOutboundRequestResponseBody($url, $status_code, $duration): bool
    {
        return static::recordEvent(function () use ($url, $status_code, $duration) {
            return app(LaritorOverride::class)->recordOutboundRequestResponseBody($url, $status_code, $duration);
        }, false);
    }

    public static function whitelistedVendors(): array
    {
        return [];
    }
}