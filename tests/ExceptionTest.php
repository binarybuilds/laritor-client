<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Recorders\ExceptionRecorder;

class ExceptionTest extends TestCase
{
    public function test_it_records_exceptions(): void
    {
        try {
            $this->get('/laritor-exception');
        } catch (\RuntimeException $e) {
            $this->assertEquals('Test exception', $e->getMessage());
        }

        $file = __DIR__.'/payloads/events.json';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');
        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey(ExceptionRecorder::$eventType, $data['events']);
        $this->assertNotEmpty( $data['events'][ExceptionRecorder::$eventType]);
        $this->assertArrayHasKey('message', $data['events'][ExceptionRecorder::$eventType][0]);
        $this->assertEquals('Test exception', $data['events'][ExceptionRecorder::$eventType][0]['message']);
    }
}
