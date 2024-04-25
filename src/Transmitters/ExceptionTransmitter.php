<?php

namespace Laritor\LaravelClient\Transmitters;

use Illuminate\Log\Events\MessageLogged;


class ExceptionTransmitter
{
    
    protected $message = '';
    
    protected $level = '';
    
    
    
    /**
     * @param MessageLogged $message
     * @return void
     */
    public static function createFromMessage(MessageLogged $message)
    {
        
    }
}