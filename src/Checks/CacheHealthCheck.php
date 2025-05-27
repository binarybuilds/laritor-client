<?php

namespace BinaryBuilds\LaritorClient\Checks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CacheHealthCheck extends BaseHealthCheck
{
    /**
     * @param Request $request
     * @return true
     */
    public function check(Request $request)
    {
        Cache::put('laritor_check', 'test');
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