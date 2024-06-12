<?php

namespace Laritor\LaravelClient\Checks;

use Illuminate\Support\Facades\DB;

class DatabaseHealthCheck extends BaseHealthCheck
{
    /**
     * @return bool
     */
    public function check()
    {
        DB::connection()->getDatabaseName();

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