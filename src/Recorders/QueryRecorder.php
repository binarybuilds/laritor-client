<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Str;
use Laritor\LaravelClient\Helpers\FileHelper;

class QueryRecorder extends Recorder
{
    use FetchesStackTrace;

    public static $eventType = 'queries';

    public static $events = [
        QueryExecuted::class
    ];

    /**
     * @param QueryExecuted $event
     * @return void
     */
    public function trackEvent($event)
    {
        if (!$this->shouldRecordQuery($event->sql)) {
            return;
        }

        if($caller = $this->getCallerFromStackTrace()) {
            $time = $event->time;

            $query = [
                'query' => $event->sql,
                'bindings' => $this->replaceBindings($event),
                'time' => $time,
                'path' => FileHelper::parseFileName($caller['file']) .'@'.$caller['line'],
//                'slow' => $time >= config('laritor.query.slow'),
//                'started_at' => now()->subMilliseconds($time)->format('Y-m-d H:i:s'),
                'completed_at' => now()->format('Y-m-d H:i:s'),
                'context' => $this->laritor->getContext()
            ];

            $this->laritor->pushEvent(static::$eventType, $query);
        }
    }

    /**
     * @param $query
     * @return bool
     */
    public function shouldRecordQuery($query)
    {
        if ($this->isReadQuery($query) && ! config('laritor.query.read')) {
            return false;
        }

        if ( $this->isWriteQuery($query) && ! config('laritor.query.write')) {
            return false;
        }

        if (app()->runningInConsole() && ! config('laritor.query.monitor_console_queries') ) {
            return false;
        }

        return true;
    }

    public function isReadQuery($query)
    {
        return Str::startsWith(strtoupper(trim($query)), ['SELECT', 'SHOW', 'DESCRIBE', 'EXPLAIN'] );
    }

    public function isWriteQuery($query)
    {
        return ! $this->isReadQuery($query);
    }

    /**
     * Format the given bindings to strings.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return array
     */
    protected function formatBindings($event)
    {
        return $event->connection->prepareBindings($event->bindings);
    }

    /**
     * Replace the placeholders with the actual bindings.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @return string
     */
    public function replaceBindings($event)
    {
        $sql = $event->sql;

        foreach ($this->formatBindings($event) as $key => $binding) {
            $regex = is_numeric($key)
                ? "/\?(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/"
                : "/:{$key}(?=(?:[^'\\\']*'[^'\\\']*')*[^'\\\']*$)/";

            if ($binding === null) {
                $binding = 'null';
            } elseif (! is_int($binding) && ! is_float($binding)) {
                $binding = $this->quoteStringBinding($event, $binding);
            }

            $sql = preg_replace($regex, $binding, $sql, 1);
        }

        return $sql;
    }

    /**
     * Add quotes to string bindings.
     *
     * @param  \Illuminate\Database\Events\QueryExecuted  $event
     * @param  string  $binding
     * @return string
     */
    protected function quoteStringBinding($event, $binding)
    {
        try {
            return $event->connection->getPdo()->quote($binding);
        } catch (\PDOException $e) {
            throw_if('IM001' !== $e->getCode(), $e);
        }

        // Fallback when PDO::quote function is missing...
        $binding = \strtr($binding, [
            chr(26) => '\\Z',
            chr(8) => '\\b',
            '"' => '\"',
            "'" => "\'",
            '\\' => '\\\\',
        ]);

        return "'".$binding."'";
    }

    /**
     * @param $events
     * @return array
     */
    public static function transformEvents($events)
    {
        $queries = collect($events);

        $duplicateBindings = $queries->duplicates('bindings')->toArray();
        $duplicatePath = $queries->duplicates('path')->toArray();

        $queries = $queries->map(function ($query) use ( $duplicateBindings, $duplicatePath){
            $query['issues'] = [];
            $path = explode('@', $query['path']);
            $query['file'] = $path[0];
            $query['line'] = $path[1];
            if ( in_array($query['bindings'], $duplicateBindings) ) {
                $query['issues'][] = 'duplicate';
            } elseif (in_array($query['path'], $duplicatePath) ) {
                $query['issues'][] = 'n-plus-1';
            }

            $query['bindings'] = config('laritor.query.record_bindings') ? $query['bindings'] : '';

            unset($query['path']);

            return $query;
        });

        unset($duplicateBindings);
        unset($duplicatePath);

        return $queries->toArray();
    }
}
