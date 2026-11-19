<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Recorders\QueryRecorder;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;

class QueryTest extends TestCase
{
    public function test_it_records_database_queries(): void
    {
        $this->get('/laritor-query')->assertStatus(200);

        $file = __DIR__.'/payloads/events.json';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');
        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey(QueryRecorder::$eventType, $data['events']);
        $this->assertNotEmpty( $data['events'][QueryRecorder::$eventType]);
        $this->assertArrayHasKey('query', $data['events'][QueryRecorder::$eventType][count($data['events'][QueryRecorder::$eventType]) - 1]);
        $this->assertEquals('SELECT 1 as ok', $data['events'][QueryRecorder::$eventType][count($data['events'][QueryRecorder::$eventType]) - 1]['query']);
    }

    public function test_it_omits_only_invalid_utf8_bindings(): void
    {
        $event = new QueryExecuted('SELECT ? as value, ? as label', ["\xFF\xFE", 'example'], 1, DB::connection());

        $this->assertSame(
            "SELECT '[binary binding omitted]' as value, 'example' as label",
            $this->app->make(QueryRecorder::class)->replaceBindings($event)
        );
    }

    public function test_it_omits_only_null_byte_bindings(): void
    {
        $event = new QueryExecuted('SELECT ? as value, ? as label', ["binary\0value", 'example'], 1, DB::connection());

        $this->assertSame(
            "SELECT '[binary binding omitted]' as value, 'example' as label",
            $this->app->make(QueryRecorder::class)->replaceBindings($event)
        );
    }

    public function test_it_still_formats_text_and_scalar_bindings(): void
    {
        $event = new QueryExecuted('SELECT ?, ?, ?, ?, ?', ["O'Reilly", 'こんにちは', 42, null, true], 1, DB::connection());

        $this->assertSame(
            "SELECT 'O''Reilly', 'こんにちは', 42, null, 1",
            $this->app->make(QueryRecorder::class)->replaceBindings($event)
        );
    }

    public function test_it_records_queries_with_binary_bindings(): void
    {
        $this->app['router']->get('/laritor-binary-query', function () {
            DB::select('SELECT ? as invalid_utf8, ? as null_byte', ["\xFF\xFE", "binary\0value"]);

            return response('OK', 200);
        });

        $this->get('/laritor-binary-query')->assertStatus(200);

        $data = json_decode(file_get_contents(__DIR__.'/payloads/events.json'), true, 512, JSON_THROW_ON_ERROR);
        $queries = $data['events'][QueryRecorder::$eventType];
        $query = $queries[count($queries) - 1];

        $this->assertSame('SELECT ? as invalid_utf8, ? as null_byte', $query['query']);
        $this->assertSame(
            "SELECT '[binary binding omitted]' as invalid_utf8, '[binary binding omitted]' as null_byte",
            $query['bindings']
        );
        $this->assertArrayHasKey('time', $query);
        $this->assertArrayHasKey('connection', $query);
    }

    public function test_it_preserves_binding_positions_and_the_original_event(): void
    {
        $bindings = [96, "\xFF", 'Ready? $1', "binary\0value", "O'Reilly", null];
        $event = new QueryExecuted("SELECT '?' as literal, ?, ?, ?, ?, ?, ?", $bindings, 1, DB::connection());

        $this->assertSame(
            "SELECT '?' as literal, 96, '[binary binding omitted]', 'Ready? $1', '[binary binding omitted]', 'O''Reilly', null",
            $this->app->make(QueryRecorder::class)->replaceBindings($event)
        );
        $this->assertSame($bindings, $event->bindings);
    }
}
