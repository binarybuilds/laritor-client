<?php

namespace Laritor\LaravelClient\Recorders;

use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Str;
use Laritor\LaravelClient\Laritor;

class QueryRecorder extends Recorder
{
    use FetchesStackTrace;

    public static $events = [
        QueryExecuted::class
    ];

    public function __construct( Laritor $laritor )
    {
        parent::__construct( $laritor );
    }

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
                'query_bindings' => $this->replaceBindings($event),
                'time' => $time,
                'file' => $caller['file'] ? Str::replaceFirst(base_path().'/', '', $caller['file']) : '',
                'line' => $caller['line'],
                'issues' => []
            ];

            $query['location'] = $query['line'] . '-'.$query['file'];

            $this->laritor->pushEvent('queries', $query);
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

        if ( ! $this->isReadQuery($query) && ! config('laritor.query.write')) {
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

    public static function shouldReportEvents( Laritor $laritor )
    {

        $report = false;
        $queries = collect($laritor->getEvents('queries'));

        $queries = $queries->map(function ($query) use ($queries, &$report){
            if (in_array($query['query_bindings'], $queries->duplicates('query_bindings')->toArray()) ) {
                $query['issues'][] = 'duplicate';
                $report = true;
            }

            if (in_array($query['location'], $queries->duplicates('location')->toArray()) ) {
                $query['issues'][] = 'n-plus-1';
                $report = true;
            }

            if ( $query['time'] >= config('laritor.query.slow') ) {
                $query['issues'][] = 'slow';
                $report = true;
            }

            unset($query['query_bindings']);
            unset($query['location']);

            return $query;
        });

        $laritor->addEvents('queries', $queries->values()->toArray() );

        return $report;
    }
}
