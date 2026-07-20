<?php

namespace BinaryBuilds\LaritorClient\Override;

use Illuminate\Contracts\Queue\Job;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Symfony\Component\Mime\Email;

interface LaritorOverride
{
    /**
     * @param string $cacheKey
     * @return bool
     */
    public function recordCacheHit($cacheKey): bool;

    /**
     * @param \Throwable $exception
     * @return bool
     */
    public function recordException($exception): bool;

    /**
     * @param $url
     * @param $status_code
     * @param $duration
     * @return bool
     */
    public function recordOutboundRequest($url, $status_code, $duration): bool;

    /**
     * @param $query
     * @param $duration
     * @param $path
     * @return bool
     */
    public function recordQuery($query, $duration, $path): bool;

    /**
     * @param string $connection
     * @param string $queue
     * @param string $job
     * @param string $status
     * @param int $duration
     * @return bool
     */
    public function recordQueuedJob(string $connection, string $queue, string $job, string $status, int $duration): bool;

    /**
     * @param $request
     * @param $response
     * @param $status
     * @param $duration
     * @param $user
     * @return bool
     */
    public function recordRequest($request, $response, $status, $duration, $user): bool;

    /**
     * @param string $command
     * @param string $status
     * @param int $duration
     * @return bool
     */
    public function recordCommandOrScheduledTask(string $command, string $status, $duration): bool;

    /**
     * @return bool
     */
    public function recordTaskScheduler(): bool;

    /**
     * @param $mailable
     * @param $to
     * @param $subject
     * @return bool
     */
    public function recordMail($mailable, $to, $subject): bool;

    /**
     * @param mixed $notifiable
     * @param Notification $notification
     * @return bool
     */
    public function recordNotification($notifiable, $notification): bool;

    /**
     * @param string $flag
     * @param mixed $scope
     * @return bool
     */
    public function recordFeatureFlag($flag, $scope): bool;

    /**
     * @param $level
     * @param $message
     * @param array $context
     * @return bool
     */
    public function recordLog($level, $message, array $context = []): bool;

    /**
     * @param Request $request
     * @return bool
     */
    public function isBot($request): bool;

    public function recordCommandContext(string $command, string $status, $duration): bool;

    public function recordScheduledTaskContext(string $task, string $status, $duration): bool;

    public function recordRequestContext($request, $response, $status, $duration, $user): bool;

    public function recordLogContext($level, $message): bool;

    public function recordQueuedJobContext(string $connection, string $queue, string $job, string $status, $duration): bool;

    public function recordDatabaseSchema(): bool;

    /**
     * @param $query
     * @param $duration
     * @param $path
     * @return bool
     */
    public function recordQueryBindings($query, $duration, $path): bool;

    public function recordRequestQueryParameters($request, $response, $status, $duration, $user): bool;

    public function recordRequestHeaders($request, $response, $status, $duration, $user): bool;

    public function recordRequestBody($request, $response, $status, $duration, $user): bool;

    public function recordResponseHeaders($request, $response, $status, $duration, $user): bool;

    public function recordResponseBody($request, $response, $status, $duration, $user): bool;

    public function recordSessionData($request, $response, $status, $duration, $user): bool;

    public function recordOutboundRequestHeaders($url, $status_code, $duration): bool;

    public function recordOutboundRequestBody($url, $status_code, $duration): bool;

    public function recordOutboundRequestResponseHeaders($url, $status_code, $duration): bool;

    public function recordOutboundRequestResponseBody($url, $status_code, $duration): bool;

    public function whitelistedVendors(): array;
}