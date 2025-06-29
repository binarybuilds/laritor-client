<?php

namespace BinaryBuilds\LaritorClient\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

/**
 * Class QueueHealthCheck
 * @package BinaryBuilds\LaritorClient\Jobs
 */
class QueueHealthCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var int
     */
    protected $checkId;

    /**
     * QueueHealthCheck constructor.
     * @param int $check_id
     */
    public function __construct($check_id)
    {
        $this->checkId = $check_id;
    }

    public function handle()
    {
        Http::withHeader('X-Api-Key', config('laritor.keys.backend'))
            ->withUserAgent('laritor-client')
            ->post(rtrim(config('laritor.ingest_endpoint'),'/').'/ack-hc', [
                'env' => !empty(config('laritor.env')) ? config('laritor.env') : config('app.env'),
                'check_id' => $this->checkId
            ]);
    }
}