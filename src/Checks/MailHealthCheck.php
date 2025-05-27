<?php

namespace BinaryBuilds\LaritorClient\Checks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class MailHealthCheck extends BaseHealthCheck
{
    /**
     * @param Request $request
     * @return true
     */
    public function check(Request $request)
    {
        Mail::send([], [], function ($message) use ($request) {
            $message->to($request->input('recipient'))
                ->subject($request->input('subject'))
                ->setBody($request->input('body'));
        });

        return true;
    }

    /**
     * @return string
     */
    public function successMessage()
    {
        return 'test mail successfully sent';
    }
}