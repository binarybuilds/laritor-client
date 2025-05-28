<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Recorders\NotificationRecorder;

class NotificationTest extends TestCase
{
    /** @test */
    public function it_records_notifications(): void
    {
        $this->get('/laritor-notification')->assertStatus(200);

        $file = __DIR__.'/payloads/events.json';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');
        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey(NotificationRecorder::$eventType, $data['events']);
        $this->assertNotEmpty( $data['events'][NotificationRecorder::$eventType]);
        $this->assertArrayHasKey('notifiable', $data['events'][NotificationRecorder::$eventType][0]);
        $this->assertEquals('Anonymous: test@example.com', $data['events'][NotificationRecorder::$eventType][0]['notifiable']);
    }
}
