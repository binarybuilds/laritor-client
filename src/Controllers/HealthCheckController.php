<?php

namespace Laritor\LaravelClient\Controllers;

use Illuminate\Http\Request;
use Laritor\LaravelClient\Checks\BaseHealthCheck;
use Laritor\LaravelClient\Checks\CacheHealthCheck;
use Laritor\LaravelClient\Checks\DatabaseHealthCheck;
use Laritor\LaravelClient\Checks\MailHealthCheck;
use Laritor\LaravelClient\Checks\QueueWorkerHealthCheck;
use Laritor\LaravelClient\Checks\SessionHealthCheck;
use Laritor\LaravelClient\Checks\StorageHealthCheck;


class HealthCheckController
{
    /**
     * @param Request $request
     * @param $check_type
     * @return mixed
     */
    public function check(Request $request, $check_type )
    {
        if (
            $request->input('ingest_url') !== rtrim(config('laritor.ingest_url'), '/')
        )
        {
            return response()->json(['message' => 'unauthorized'], 401);
        }

        switch ($check_type) {
            case 'db' : $health_check = app( DatabaseHealthCheck::class );break;
            case 'cache' : $health_check = app( CacheHealthCheck::class );break;
            case 'mail' : $health_check = app( MailHealthCheck::class );break;
            case 'session' : $health_check = app( SessionHealthCheck::class );break;
            case 'storage' : $health_check = app( StorageHealthCheck::class );break;
            case 'queue' : $health_check = app( QueueWorkerHealthCheck::class );break;
            default: {

                $health_check_class = app()->getNamespace()."Laritor\\$check_type";

                if (class_exists($health_check_class)) {
                    $health_check = app( $health_check_class );
                } else {
                    $health_check = app( BaseHealthCheck::class );
                }
            } break;
        }

        return $health_check->run($request);
    }
}