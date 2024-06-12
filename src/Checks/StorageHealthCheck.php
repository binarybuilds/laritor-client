<?php

namespace Laritor\LaravelClient\Checks;

use Illuminate\Support\Facades\Storage;

class StorageHealthCheck extends BaseHealthCheck
{
    /**
     * @return bool
     */
    public function check()
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