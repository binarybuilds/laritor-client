<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Recorders\OutboundRequestRecorder;

class ExternalHttpTest extends TestCase
{
    public function test_it_records_external_requests(): void
    {
        $this->get('/laritor-external-http')->assertStatus(200);

        $file = __DIR__.'/payloads/events.json';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');
        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey(OutboundRequestRecorder::$eventType, $data['events']);
        $this->assertNotEmpty( $data['events'][OutboundRequestRecorder::$eventType]);
        $this->assertArrayHasKey('url', $data['events'][OutboundRequestRecorder::$eventType][0]);
        $this->assertEquals('https://example.com', $data['events'][OutboundRequestRecorder::$eventType][0]['url']);
        $this->assertArrayHasKey('hello', $data['events'][OutboundRequestRecorder::$eventType][0]['request']['body']);
        $this->assertArrayHasKey('custom-header', $data['events'][OutboundRequestRecorder::$eventType][0]['request']['headers']);
        $this->assertNotEmpty($data['events'][OutboundRequestRecorder::$eventType][0]['response']['body']);
        $this->assertNotEmpty($data['events'][OutboundRequestRecorder::$eventType][0]['response']['headers']);
    }
}
