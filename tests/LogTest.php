<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Recorders\LogRecorder;

class LogTest extends TestCase
{
    public function test_it_records_logs(): void
    {
        $this->get('/laritor-log')->assertStatus(200);

        $file = __DIR__.'/payloads/events.json';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');
        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey(LogRecorder::$eventType, $data['events']);
        $this->assertNotEmpty( $data['events'][LogRecorder::$eventType]);
        $this->assertArrayHasKey('message', $data['events'][LogRecorder::$eventType][0]);
        $this->assertStringStartsWith('This is a test log', $data['events'][LogRecorder::$eventType][0]['message']);
    }

    public function test_it_redacts_logs(): void
    {
        $this->get('/laritor-log')->assertStatus(200);

        $file = __DIR__.'/payloads/events.json';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');
        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey(LogRecorder::$eventType, $data['events']);
        $this->assertNotEmpty( $data['events'][LogRecorder::$eventType]);
        $this->assertArrayHasKey('message', $data['events'][LogRecorder::$eventType][0]);
        $this->assertStringNotContainsString('378282246310005', $data['events'][LogRecorder::$eventType][0]['message']);
        $this->assertNotEquals('sensitive key',$data['events'][LogRecorder::$eventType][0]['log_context']['Authorization']);
    }
}
