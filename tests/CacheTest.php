<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Recorders\CacheRecorder;

class CacheTest extends TestCase
{
    public function test_it_records_cache_hits(): void
    {
        $this->get('/laritor-cache')->assertStatus(200);

        $file = __DIR__.'/payloads/events.json';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');
        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey(CacheRecorder::$eventType, $data['events']);
        $this->assertNotEmpty( $data['events'][CacheRecorder::$eventType]);
        $this->assertArrayHasKey('key', $data['events'][CacheRecorder::$eventType][0]);
        $this->assertEquals('foo', $data['events'][CacheRecorder::$eventType][0]['key']);
    }
}
