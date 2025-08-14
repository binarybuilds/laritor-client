<?php

namespace BinaryBuilds\LaritorClient\Tests;

use Illuminate\Support\Facades\DB;

class SchemaTest extends TestCase
{
    public function test_it_records_database_schema(): void
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
        $driver =  (int)app()->version() >= 9 ? DB::getDriverName() : config('database.connections.'.config('database.default').'driver');
        $this->assertSame($driver, $data['data']['db_schema']['database']);
        $this->assertArrayHasKey('version', $data['data']['db_schema']);
        $this->assertArrayHasKey('tables', $data['data']['db_schema']);
        $this->assertNotEmpty($data['data']['db_schema']['tables']);
    }
}
