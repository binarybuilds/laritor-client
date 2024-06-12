<?php

namespace Laritor\LaravelClient\Checks;

use Illuminate\Support\Facades\Mail;

class MailHealthCheck extends BaseHealthCheck
{
    /**
     * @return bool
     */
    public function check()
    {
        Mail::send([], [], function ($message) {
            $message->to('test@test.com')
                ->subject('A test email')
                ->setBody('This is the email content.');
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