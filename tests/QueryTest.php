<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Recorders\QueryRecorder;

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
}
