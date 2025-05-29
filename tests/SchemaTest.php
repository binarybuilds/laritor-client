<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Recorders\QueuedJobRecorder;
use Illuminate\Support\Facades\DB;

class SchemaTest extends TestCase
{
    /** @test */
    public function it_records_database_schema(): void
    {
       $this->artisan('laritor:sync');

        $file = __DIR__.'/payloads/events.json';
        $this->assertFileExists($file);

        $data = json_decode(file_get_contents($file), true);
        $this->assertIsArray($data, 'Payload is not valid JSON');
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('db_schema', $data['data']);
        $this->assertNotEmpty( $data['data']['db_schema']);
        $this->assertArrayHasKey('database', $data['data']['db_schema']);
        $this->assertSame(DB::getDriverName(), $data['data']['db_schema']['database']);
        $this->assertArrayHasKey('version', $data['data']['db_schema']);
        $this->assertArrayHasKey('tables', $data['data']['db_schema']);
        $this->assertNotEmpty($data['data']['db_schema']['tables']);
    }
}
