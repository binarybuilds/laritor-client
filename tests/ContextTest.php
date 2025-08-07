<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Recorders\RequestRecorder;

class ContextTest extends TestCase
{
    public function test_it_records_request_context(): void
    {
        $this->post('/laritor-test', [
            'hello' => 'world',
        ])->assertStatus(200);

        $path = __DIR__ . '/payloads/events.json';

        $data = json_decode(file_get_contents($path), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');


        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey(RequestRecorder::$eventType, $data['events']);
        $this->assertArrayHasKey('request', $data['events'][RequestRecorder::$eventType][0]);
        $this->assertArrayHasKey('custom_context', $data['events'][RequestRecorder::$eventType][0]);

        if ((int)$this->app->version() >= 11) {
            $this->assertArrayHasKey('added_context', $data['events'][RequestRecorder::$eventType][0]['custom_context']);
            $this->assertEquals('custom context added', $data['events'][RequestRecorder::$eventType][0]['custom_context']['added_context']);
        } else {
            $this->assertEmpty($data['events'][RequestRecorder::$eventType][0]['custom_context']);
        }
    }
}
