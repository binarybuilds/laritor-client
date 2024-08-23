<?php

namespace Laritor\LaravelClient\Checks;

use Illuminate\Http\Request;
use Laritor\LaravelClient\Jobs\QueueHealthCheck;

class QueueWorkerHealthCheck extends BaseHealthCheck
{
    /**
     * @param Request $request
     * @return true
     */
    public function check(Request $request)
    {
        $queue = QueueHealthCheck::dispatch($request->input('check_id'));

        if ($request->input('connection')) {
            $queue->onConnection($request->input('connection'));
        }

        if ($request->input('queue')) {
            $queue->onQueue($request->input('queue'));
        }

        return true;
    }

    /**
     * @return string
     */
    public function successMessage()
    {
        return '';
    }
}