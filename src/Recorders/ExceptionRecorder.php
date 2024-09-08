<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Str;
use Laritor\LaravelClient\Helpers\FileHelper;

class ExceptionRecorder extends Recorder
{
    /**
     * @var string
     */
    public static $eventType = 'exceptions';

    /**
     * Handle the event.
     *
     * @param  \Throwable $event
     * @return void
     */
    public function trackEvent($event)
    {
        $throwable = $event;
        if (!$this->shouldReportException(get_class($throwable))) {
            return;
        }

        $data = [
            'message' => $throwable->getMessage(),
            'level' => 'error',
            'exception_class' => get_class($throwable),
            'stacktrace' => [],
        ];

        $data['stacktrace'][] = [
            'file' => FileHelper::parseFileName($throwable->getFile()),
            'line' => $throwable->getLine(),
            'file_contents' => $this->getFileContents($throwable->getFile(), $throwable->getLine())
        ];

        $iteration = 1;
        foreach ($throwable->getTrace() as $trace) {
            $data['stacktrace'][] = [
                'file' => FileHelper::parseFileName($trace['file']),
                'line' => $trace['line'] ?? '',
                'file_contents' => isset($trace['file']) && $iteration <= 20 ? $this->getFileContents($trace['file'], $trace['line']) : []
            ];
            $iteration++;
        }

        $this->laritor->pushEvent(static::$eventType, $data);
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

    public static function registerRecorder()
    {
        app()->afterResolving(ExceptionHandler::class, function (ExceptionHandler $handler){
            $handler->reportable(function (\Throwable $exception){
                app(ExceptionRecorder::class)->handle($exception);
            });
        });
    }
}
