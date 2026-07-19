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
        return Str::doesntContain($url, 'laritor.net') &&
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
        return Str::doesntContain($job, 'QueueHealthCheck') &&
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
        return Str::doesntContain($command, self::$ignoredCommands) &&
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
}