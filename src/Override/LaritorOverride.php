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
     * @param string $url
     * @return bool
     */
    public function recordOutboundRequest($url): bool;

    /**
     * @param string $query
     * @param int $duration
     * @return bool
     */
    public function recordQuery($query, $duration): bool;

    /**
     * @param Job $job
     * @return bool
     */
    public function recordQueuedJob($job): bool;

    /**
     * @param Request $request
     * @return bool
     */
    public function recordRequest($request): bool;

    /**
     * @param string $command
     * @return bool
     */
    public function recordCommandOrScheduledTask($command): bool;

    /**
     * @return bool
     */
    public function recordTaskScheduler(): bool;

    /**
     * @param \Swift_Message|Email $message
     * @return bool
     */
    public function recordMail($message): bool;

    /**
     * @param mixed $notifiable
     * @param Notification $notification
     * @return bool
     */
    public function recordNotification($notifiable, $notification): bool;

    /**
     * @param Request $request
     * @return bool
     */
    public function isBot($request): bool;
}