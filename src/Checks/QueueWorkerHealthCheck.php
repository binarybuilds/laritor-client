<?php

namespace BinaryBuilds\LaritorClient\Checks;

use Illuminate\Http\Request;
use BinaryBuilds\LaritorClient\Jobs\QueueHealthCheck;

class QueueWorkerHealthCheck extends BaseHealthCheck
{
    /**
     * @var bool
     */
    protected $ping_back = true;

    /**
     * @var null
     */
    public $connection = null;

    /**
     * @var null
     */
    public $queue = null;

    /**
     * @param Request $request
     * @return true
     */
    public function check(Request $request)
    {
        $queue = QueueHealthCheck::dispatch($request->input('check_id'));

        if ($this->connection) {
            $queue->onConnection($request->input('connection'));
        }

        if ($this->queue) {
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