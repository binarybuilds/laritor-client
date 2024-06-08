<?php

namespace Laritor\LaravelClient\Recorders;

use Laritor\LaravelClient\Laritor;

class Recorder
{
    /**
     * @var Laritor
     */
    protected $laritor;

    /**
     * @param Laritor $laritor
     */
    public function __construct(Laritor $laritor)
    {
        $this->laritor = $laritor;
    }

    /**
     * @param $event
     * @return void
     */
    public function handle($event)
    {
        try{
            $this->trackEvent($event);
        } catch (\Throwable $exception) {

        }
    }

    public function trackEvent( $event )
    {
    }
}
