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
     * @param $url
     * @param $status_code
     * @param $duration
     * @return bool
     */
    public function recordOutboundRequest($url, $status_code, $duration): bool
    {
        return true;
    }

    /**
     * @param $query
     * @param $duration
     * @param $path
     * @return bool
     */
    public function recordQuery($query, $duration, $path): bool
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
     * @param string $connection
     * @param string $queue
     * @param string $job
     * @param string $status
     * @param int $duration
     * @return bool
     */
    public function recordQueuedJob(string $connection, string $queue, string $job, string $status, int $duration): bool
    {
        return true;
    }

    /**
     * @param $request
     * @param $response
     * @param $status
     * @param $duration
     * @param $user
     * @return bool
     */
    public function recordRequest($request, $response, $status, $duration, $user): bool
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
     * @param string $status
     * @param int $duration
     * @return bool
     */
    public function recordCommandOrScheduledTask(string $command, string $status, int $duration): bool
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
     * @param $mailable
     * @param $to
     * @param $subject
     * @return bool
     */
    public function recordMail($mailable, $to, $subject): bool
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
     * @param $level
     * @param $message
     * @param array $context
     * @return bool
     */
    public function recordLog($level, $message, array $context = []): bool
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