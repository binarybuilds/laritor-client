<?php

namespace Laritor\LaravelClient\Checks;

use Illuminate\Support\Facades\Session;

class SessionHealthCheck extends BaseHealthCheck
{
    /**
     * @return bool
     */
    public function check()
    {
        Session::get('laritor_check');

        return true;
    }

    /**
     * @return string
     */
    public function successMessage()
    {
        return 'session hit successful';
    }
}