<?php

namespace Laritor\LaravelClient\Checks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SessionHealthCheck extends BaseHealthCheck
{
    /**
     * @param Request $request
     * @return true
     */
    public function check(Request $request)
    {
        Session::put('laritor_check', 'test');
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