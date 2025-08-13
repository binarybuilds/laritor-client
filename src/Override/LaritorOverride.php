<?php

namespace BinaryBuilds\LaritorClient\Override;

interface LaritorOverride
{
    public function recordCacheHit($cacheKey): bool;

    public function recordException($exception): bool;

    public function recordOutboundRequest($url): bool;

    public function recordQuery($query, $duration): bool;

    public function recordQueuedJob($job): bool;

    public function recordRequest($request): bool;

    public function recordCommandOrScheduledTask($command): bool;

    public function recordTaskScheduler(): bool;

    public function recordMail($message): bool;

    public function recordNotification($notifiable, $notification): bool;

    public function isBot($request): bool;
}