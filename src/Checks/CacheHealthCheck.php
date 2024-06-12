<?php

namespace Laritor\LaravelClient\Checks;

use Illuminate\Support\Facades\Cache;

class CacheHealthCheck extends BaseHealthCheck
{
    /**
     * @return bool
     */
    public function check()
    {
        Cache::get('laritor_check');

        return true;
    }

    /**
     * @return string
     */
    public function successMessage()
    {
        return 'cache hit successful';
    }
}