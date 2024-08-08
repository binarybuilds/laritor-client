<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Str;
use Laritor\LaravelClient\Helpers\FileHelper;
use Laritor\LaravelClient\Laritor;

class ExceptionRecorder extends Recorder
{
    /**
     * @var string[]
     */
    public static $events = [
        MessageLogged::class
    ];

    /**
     * Handle the event.
     *
     * @param  MessageLogged  $event
     * @return void
     */
    public function trackEvent($event)
    {
        if (!$this->shouldReportException($event->context['exception'])) {
            return;
        }

        $data = [
            'message' => $event->message,
            'level' => $event->level,
            'exception_class' => get_class($event->context['exception']),
            'stacktrace' => [],
        ];

        $data['stacktrace'][] = [
            'file' => Str::replaceFirst(base_path().'/', '', $event->context['exception']->getFile()),
            'line' => $event->context['exception']->getLine(),
            'file_contents' => $this->getFileContents($event->context['exception']->getFile(), $event->context['exception']->getLine())
        ];

        $iteration = 1;
        foreach ($event->context['exception']->getTrace() as $trace) {
            $data['stacktrace'][] = [
                'file' => $trace['file'] ? Str::replaceFirst(base_path().'/', '', $trace['file']) : '',
                'line' => $trace['line'] ?? '',
                'file_contents' => isset($trace['file']) && $iteration <= 20 ? $this->getFileContents($trace['file'], $trace['line']) : []
            ];
            $iteration++;
        }

        $this->laritor->pushEvent('exceptions', $data);
    }

    public function shouldReportException($exception)
    {
        foreach ((array)config('laritor.exceptions.ignore') as $ignore ) {

            if ($exception instanceof $ignore ) {
                return false;
            }
        }

        return true;
    }

    private function getFileContents(string $filePath, int $line, int $before = 10, int $after = 10 )
    {
        if (Str::contains($filePath, ['vendor/', 'public/index.php'])) {
            return [];
        }

        return FileHelper::createFromPath($filePath)
            ->offset($line)
            ->before($before)
            ->after($after)
            ->getContents();
    }

    public static function shouldReportEvents( Laritor $laritor )
    {
        return true;
    }
}
