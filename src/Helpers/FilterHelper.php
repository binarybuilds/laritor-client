<?php

namespace BinaryBuilds\LaritorClient\Helpers;

use BinaryBuilds\LaritorClient\Override\LaritorOverride;

class FilterHelper
{
    public static function recordEvent(callable $callable, $default = true)
    {
        return rescue($callable, $default);
    }

    public static function recordCacheHit($cacheKey): bool
    {
        return static::recordEvent(function () use ($cacheKey) {
            return app(LaritorOverride::class)->recordCacheHit($cacheKey);
        });
    }

    public static function recordException($exception): bool
    {
        return static::recordEvent(function () use ($exception) {
            return app(LaritorOverride::class)->recordException($exception);
        });
    }

    public static function recordOutboundRequest($url): bool
    {
        return static::recordEvent(function () use ($url) {
            return app(LaritorOverride::class)->recordOutboundRequest($url);
        });
    }

    public static function recordQuery($query, $duration): bool
    {
        return static::recordEvent(function () use ($query, $duration) {
            return app(LaritorOverride::class)->recordQuery($query, $duration);
        });
    }

    public static function recordQueuedJob($job): bool
    {
        return static::recordEvent(function () use ($job) {
            return app(LaritorOverride::class)->recordQueuedJob($job);
        });
    }

    public static function recordRequest($request): bool
    {
        return static::recordEvent(function () use ($request) {
            return app(LaritorOverride::class)->recordRequest($request);
        });
    }

    public static function recordCommandOrScheduledTask($command): bool
    {
        return static::recordEvent(function () use ($command) {
            return app(LaritorOverride::class)->recordCommandOrScheduledTask($command);
        });
    }

    public static function recordTaskScheduler(): bool
    {
        return static::recordEvent(function (){
            return app(LaritorOverride::class)->recordTaskScheduler();
        });
    }

    public static function recordMail($message): bool
    {
        return static::recordEvent(function () use ($message) {
            return app(LaritorOverride::class)->recordMail($message);
        });
    }

    public static function recordNotification($notifiable, $notification): bool
    {
        return static::recordEvent(function () use ($notifiable, $notification) {
            return app(LaritorOverride::class)->recordNotification($notifiable, $notification);
        });
    }

    public static function isBot($request): bool
    {
        return static::recordEvent(function () use ($request) {
            return app(LaritorOverride::class)->isBot($request);
        }, false);
    }
}