<?php

namespace BinaryBuilds\LaritorClient;

use Closure;
class SendEventsMiddleware
{
    public function handle($request, Closure $next)
    {
        app(Laritor::class)->controllerStarted();

        return $next($request);
    }

    public function terminate($request, $response)
    {
        app(Laritor::class)->sendEvents();
    }
}