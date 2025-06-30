<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Recorders\MailRecorder;

class MailTest extends TestCase
{
    public function test_it_records_mails(): void
    {
        $this->get('/mail-test')->assertStatus(200);

        $file = __DIR__.'/payloads/events.json';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');
        $this->assertArrayHasKey('events', $data);
        $this->assertArrayHasKey(MailRecorder::$eventType, $data['events']);
        $this->assertNotEmpty( $data['events'][MailRecorder::$eventType]);
        $this->assertArrayHasKey('subject', $data['events'][MailRecorder::$eventType][0]);
        $this->assertEquals('Test', $data['events'][MailRecorder::$eventType][0]['subject']);
    }
}
