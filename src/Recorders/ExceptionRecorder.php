<?php

namespace BinaryBuilds\LaritorClient\Recorders;

use BinaryBuilds\LaritorClient\Helpers\DataHelper;
use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Str;
use BinaryBuilds\LaritorClient\Helpers\FileHelper;

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

        if (!FilterHelper::recordException($throwable)) {
            return;
        }

        $data = [
            'message' => DataHelper::redactData($throwable->getMessage()),
            'level' => 'error',
            'exception_class' => get_class($throwable),
            'stacktrace' => [],
            'occurred_at' => now()->format('Y-m-d H:i:s'),
            'context' => $this->laritor->getContext()
        ];

        $data['stacktrace'][] = [
            'file' => FileHelper::parseFileName($throwable->getFile()),
            'line' => $throwable->getLine(),
            'file_contents' => $this->getFileContents($throwable->getFile(), $throwable->getLine())
        ];

        $iteration = 1;
        foreach ($throwable->getTrace() as $trace) {
            if (isset($trace['file'])) {
                $data['stacktrace'][] = [
                    'file' => FileHelper::parseFileName($trace['file']),
                    'line' => $trace['line'] ?? '',
                    'file_contents' => isset($trace['file']) && $iteration <= 20 ? $this->getFileContents($trace['file'], $trace['line']) : []
                ];
                $iteration++;
            }
        }

        $this->laritor->pushEvent(static::$eventType, $data);
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
