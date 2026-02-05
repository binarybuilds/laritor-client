<?php

namespace BinaryBuilds\LaritorClient\Override;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Symfony\Component\Mime\Email;

class DefaultOverride implements LaritorOverride
{
    /**
     * @param string $cacheKey
     * @return bool
     */
    public function recordCacheHit($cacheKey): bool
    {
        $ignore = [
            'illuminate:queue',
            'laravel:',
            'telescope:'
        ];

        return ! Str::startsWith($cacheKey, $ignore);
    }

    /**
     * @param \Throwable $exception
     * @return bool
     */
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

    /**
     * @param string $url
     * @return bool
     */
    public function recordOutboundRequest($url): bool
    {
        return true;
    }

    /**
     * @param string $query
     * @param int $duration
     * @return bool
     */
    public function recordQuery($query, $duration): bool
    {
        $ignore = [
            "`".config('session.table')."`",
            "`".config('cache.stores.database.table')."`",
            "`".config('queue.connections.database.table')."`",
            "`".config('pennant.stores.database.table')."`",
            "`telescope_entries`",
            "`telescope_entries_tags`",
            "`pulse_entries`",
            "`pulse_aggregates`",
            "`action_events`",
        ];

        return ! Str::contains($query, $ignore);
    }

    /**
     * @param Job $job
     * @return bool
     */
    public function recordQueuedJob($job): bool
    {
        return true;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function recordRequest($request): bool
    {
        $ignore = [
            'telescope/*'.
            'pulse/*',
            '_debugbar*',
            '__clockwork*',
            '_ignition/*',
            '*livewire.min.js*'
        ];

        foreach ($ignore as $ignored ) {
            if ($request->is($ignored)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param string $command
     * @return bool
     */
    public function recordCommandOrScheduledTask($command): bool
    {
        return true;
    }

    /**
     * @return bool
     */
    public function recordTaskScheduler(): bool
    {
        return true;
    }

    /**
     * @param Email $message
     * @return bool
     */
    public function recordMail($message): bool
    {
        return true;
    }

    /**
     * @param mixed $notifiable
     * @param Notification $notification
     * @return bool
     */
    public function recordNotification($notifiable, $notification): bool
    {
        return true;
    }

    /**
     * @param string $flag
     * @param mixed $scope
     * @return bool
     */
    public function recordFeatureFlag($flag, $scope): bool
    {
        return true;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function isBot($request): bool
    {
        $userAgent = $request->userAgent();
        $crawler = new CrawlerDetect();
        return $crawler->isCrawler($userAgent);
    }
}