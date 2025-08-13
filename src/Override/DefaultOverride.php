<?php

namespace BinaryBuilds\LaritorClient\Override;

use Jaybizzle\CrawlerDetect\CrawlerDetect;

class DefaultOverride implements LaritorOverride
{
    public function recordCacheHit($cacheKey): bool
    {
        return true;
    }

    public function recordException($exception): bool
    {
        $ignore = [
            \Illuminate\Database\Eloquent\ModelNotFoundException::class,
            \Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class,
            \Illuminate\Auth\Access\AuthorizationException::class,
            \Illuminate\Auth\AuthenticationException::class,
            \Illuminate\Validation\ValidationException::class,
        ];

        foreach ($ignore as $ignored ) {
            if ($exception instanceof $ignored) {
                return false;
            }
        }

        return true;
    }

    public function recordOutboundRequest($url): bool
    {
        return true;
    }

    public function recordQuery($query, $duration): bool
    {
        return true;
    }

    public function recordQueuedJob($job): bool
    {
        return true;
    }

    public function recordRequest($request): bool
    {
        $ignore = [
            'telescope/*'.
            'pulse/*',
            '_debugbar*',
            '__clockwork*',
            '_ignition/*',
        ];

        foreach ($ignore as $ignored ) {
            if ($request->is($ignored)) {
                return false;
            }
        }

        return true;
    }

    public function recordCommandOrScheduledTask($command): bool
    {
        return true;
    }

    public function recordTaskScheduler(): bool
    {
        return true;
    }

    public function recordMail($message): bool
    {
        return true;
    }

    public function recordNotification($notifiable, $notification): bool
    {
        return true;
    }

    public function isBot($request): bool
    {
        $userAgent = $request->userAgent();
        $crawler = new CrawlerDetect();
        return $crawler->isCrawler($userAgent);
    }
}