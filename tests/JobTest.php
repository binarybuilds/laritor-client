<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Recorders\ExceptionRecorder;
use BinaryBuilds\LaritorClient\Recorders\QueuedJobRecorder;

class JobTest extends TestCase
{
    public function test_it_records_jobs(): void
    {
        $this->get('/laritor-job')->assertStatus(200);

        $file = __DIR__.'/payloads/events.json';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');
        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey(QueuedJobRecorder::$eventType, $data['events']);
        $this->assertNotEmpty( $data['events'][QueuedJobRecorder::$eventType]);
    }

    public function test_it_records_failed_jobs(): void
    {
        $this->get('/laritor-failed-job')->assertStatus(200);

        $file = __DIR__.'/payloads/events.json';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');
        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey(QueuedJobRecorder::$eventType, $data['events']);
        $this->assertNotEmpty( $data['events'][QueuedJobRecorder::$eventType]);
        $this->assertArrayHasKey(ExceptionRecorder::$eventType, $data['events']);
        $this->assertNotEmpty( $data['events'][ExceptionRecorder::$eventType]);
    }
}
