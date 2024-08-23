<?php

namespace Laritor\LaravelClient\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class QueueHealthCheck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $checkId;

    /**
     * @param $check_id
     */
    public function __construct($check_id)
    {
        $this->checkId = $check_id;
    }

    public function handle()
    {
        Http::post('http:/159.223.153.239/api/queue-hc', [ 'check_id' => $this->checkId ]);
    }
}