<?php

namespace BinaryBuilds\LaritorClient\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use BinaryBuilds\LaritorClient\Checks\BaseHealthCheck;
use BinaryBuilds\LaritorClient\Checks\CacheHealthCheck;
use BinaryBuilds\LaritorClient\Checks\DatabaseHealthCheck;
use BinaryBuilds\LaritorClient\Checks\MailHealthCheck;
use BinaryBuilds\LaritorClient\Checks\QueueWorkerHealthCheck;
use BinaryBuilds\LaritorClient\Checks\SessionHealthCheck;
use BinaryBuilds\LaritorClient\Checks\StorageHealthCheck;


class HealthCheckController
{
    /**
     * @param Request $request
     * @param $check_type
     * @return mixed
     */
    public function check(Request $request, $check_type )
    {
        if ( $request->input('token') !== config('laritor.keys.backend')) {
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

                $health_check_class = app()->getNamespace()."BinaryBuilds\\$check_type";

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