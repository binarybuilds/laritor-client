<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Log\Events\MessageLogged;
use Laritor\LaravelClient\Helpers\FileHelper;

class ExceptionRecorder extends Recorder
{
    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function trackEvent(MessageLogged $event)
    {
        $data = [
            'type' => 'exception',
            'message' => $event->message,
            'level' => $event->level,
            'exception_class' => get_class($event->context['exception']),
            'stacktrace' => [],
        ];

        foreach ($event->context['exception']->getTrace() as $trace) {

            array_push($data['stacktrace'], [
                'file' => $trace['file'] ?? '',
                'line' => $trace['line'] ?? '',
                'function' => $trace['function'] ?? '',
                'class' => $trace['class'] ?? '',
                'file_contents' => isset($trace['file']) ? FileHelper::createFromPath($trace['file'])
                ->offset($trace['line'])
                ->before(25)
                ->after(25)
                ->getContents() : ''
            ]);
        }

        $this->laritor->setExceptionOccurred();

        $this->laritor->addEvent($data);
    }

    public function numberOfLines()
    {
        $this->file->seek(PHP_INT_MAX);

        return $this->file->key() + 1;
    }
}
