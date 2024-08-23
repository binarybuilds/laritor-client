<?php

namespace Laritor\LaravelClient\Checks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StorageHealthCheck extends BaseHealthCheck
{
    /**
     * @param Request $request
     * @return true
     */
    public function check(Request $request)
    {
        Storage::put('laritor_hc.txt', 'laritor health check');
        Storage::delete('laritor_hc.txt');

        return true;
    }

    /**
     * @return string
     */
    public function successMessage()
    {
        return 'read and write test successful';
    }
}