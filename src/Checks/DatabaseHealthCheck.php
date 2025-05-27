<?php

namespace BinaryBuilds\LaritorClient\Checks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DatabaseHealthCheck extends BaseHealthCheck
{
    /**
     * @param Request $request
     * @return true
     */
    public function check(Request $request)
    {
        DB::select('SELECT 1');

        return true;
    }

    /**
     * @return string
     */
    public function successMessage()
    {
        return 'database is up and running';
    }
}