<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Recorders\RequestRecorder;

class RequestTest extends TestCase
{
    /** @test */
    public function it_records_requests(): void
    {
        $this->get('/laritor-test')
            ->assertStatus(200);

        $path = __DIR__ . '/payloads/events.json';

        $this->assertFileExists($path, "Expected payload at {$path}");

        $data = json_decode(file_get_contents($path), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');

        $this->assertArrayHasKey('app', $data);
        $this->assertArrayHasKey('env', $data);
        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey(RequestRecorder::$eventType, $data['events']);
        $this->assertArrayHasKey('request', $data['events'][RequestRecorder::$eventType][0]);
        $this->assertArrayHasKey('url', $data['events'][RequestRecorder::$eventType][0]['request']);
        $this->assertEquals('laritor-test', $data['events'][RequestRecorder::$eventType][0]['request']['url']);
    }
}
