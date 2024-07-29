<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Str;
use Laritor\LaravelClient\Helpers\FileHelper;

class ExceptionRecorder extends Recorder
{

    /**
     * Handle the event.
     *
     * @param  MessageLogged  $event
     * @return void
     */
    public function trackEvent($event)
    {
        $data = [
            'type' => 'exception',
            'message' => $event->message,
            'level' => $event->level,
            'exception_class' => get_class($event->context['exception']),
            'stacktrace' => [],
        ];

        foreach ($event->context['exception']->getTrace() as $trace) {
            $data['stacktrace'][] = [
                'file' => $trace['file'] ?? '',
                'line' => $trace['line'] ?? '',
                'function' => $trace['function'] ?? '',
                'class' => $trace['class'] ?? '',
                'file_contents' => isset($trace['file']) ? $this->getFileContents($trace['file'], $trace['line']) : ''
            ];
        }

        $this->laritor->setExceptionOccurred();

        $this->laritor->addEvent($data);
    }

    private function getFileContents(string $filePath, int $line, int $before = 15, int $after = 10 )
    {
        if (Str::contains($filePath, 'vendor/')) {
            return [];
        }

        return FileHelper::createFromPath($filePath)
            ->offset($line)
            ->before($before)
            ->after($after)
            ->getContents();
    }
}
