<?php

namespace Laritor\LaravelClient\Recorders;


interface RecorderInterface
{
    /**
     * @param $event
     * @return mixed
     */
    public function trackEvent($event);
}