<?php

namespace BinaryBuilds\LaritorClient\Tests;

use BinaryBuilds\LaritorClient\Helpers\FilterHelper;
use BinaryBuilds\LaritorClient\Laritor;
use BinaryBuilds\LaritorClient\Override\DefaultOverride;
use BinaryBuilds\LaritorClient\Override\LaritorOverride;
use BinaryBuilds\LaritorClient\Recorders\FeatureFlagRecorder;
use BinaryBuilds\LaritorClient\Recorders\QueryRecorder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Client\Events\RequestSending;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RecordingTest extends TestCase
{
    public function test_recording_state_belongs_to_the_resolved_singleton(): void
    {
        $original = app(Laritor::class);
        $other = new Laritor();

        Laritor::withoutRecording(function () use ($original, $other) {
            $this->assertTrue(Laritor::isRecordingPaused());
            $original->pushEvent('manual', ['name' => 'excluded']);
            $other->pushEvent('manual', ['name' => 'included']);
            $this->assertSame([], $original->getEvents('manual'));
            $this->assertCount(1, $other->getEvents('manual'));

            $this->app->instance(Laritor::class, $other);

            try {
                $this->assertFalse(Laritor::isRecordingPaused());
            } finally {
                $this->app->instance(Laritor::class, $original);
            }

            $this->assertTrue(Laritor::isRecordingPaused());
        });

        $this->assertFalse(Laritor::isRecordingPaused());
    }

    public function test_reset_clears_recording_state_for_the_next_lifecycle(): void
    {
        $laritor = app(Laritor::class);

        Laritor::withoutRecording(function () use ($laritor) {
            $this->assertTrue(Laritor::isRecordingPaused());
            $laritor->reset();
            $this->assertFalse(Laritor::isRecordingPaused());
            DB::select('SELECT 1');
        });

        $this->assertFalse(Laritor::isRecordingPaused());
        $this->assertCount(1, $laritor->getEvents(QueryRecorder::$eventType));
    }

    public function test_nested_scopes_return_values_and_restore_recording_after_exceptions(): void
    {
        $this->assertFalse(Laritor::isRecordingPaused());

        $result = Laritor::withoutRecording(function () {
            $this->assertTrue(Laritor::isRecordingPaused());
            $this->assertSame(42, Laritor::withoutRecording(fn () => 42));
            $this->assertTrue(Laritor::isRecordingPaused());

            try {
                Laritor::withoutRecording(function () {
                    throw new \RuntimeException('inner');
                });
            } catch (\RuntimeException $exception) {
                $this->assertSame('inner', $exception->getMessage());
            }

            $this->assertTrue(Laritor::isRecordingPaused());

            return 'returned';
        });

        $this->assertSame('returned', $result);
        $this->assertFalse(Laritor::isRecordingPaused());

        try {
            Laritor::withoutRecording(function () {
                throw new \RuntimeException('outer');
            });
        } catch (\RuntimeException $exception) {
            $this->assertSame('outer', $exception->getMessage());
        }

        $this->assertFalse(Laritor::isRecordingPaused());
    }

    public function test_paused_events_still_reach_application_listeners_and_recording_resumes(): void
    {
        $laritor = app(Laritor::class);
        $laritor->reset();
        $queries = 0;
        Event::listen(QueryExecuted::class, function () use (&$queries) {
            $queries++;
        });

        Laritor::withoutRecording(function () {
            DB::select('SELECT 1');
            Log::info('excluded log');
            Cache::put('excluded', 'value');
            Cache::get('excluded');
            report(new \RuntimeException('excluded exception'));
        });

        $this->assertSame(1, $queries);
        $this->assertSame([], $laritor->getEvents());
        $this->assertNull($laritor->getException());

        DB::select('SELECT 2');

        $this->assertSame(2, $queries);
        $this->assertCount(1, $laritor->getEvents(QueryRecorder::$eventType));
        $this->assertSame('SELECT 2', $laritor->getEvents(QueryRecorder::$eventType)[0]['query']);
    }

    public function test_manual_events_and_custom_logs_respect_the_pause(): void
    {
        $laritor = app(Laritor::class);
        $laritor->reset();
        config(['laritor.ingest_events_without_occurrence' => false]);

        Laritor::withoutRecording(function () use ($laritor) {
            Laritor::addCustomEvent('excluded');
            $laritor->addCustomLog('internal', 'info', 'excluded');
            FeatureFlagRecorder::recordFeatureCheck('excluded');
            $this->assertSame($laritor, $laritor->pushEvent('manual', ['name' => 'excluded']));
        });

        $this->assertSame([], $laritor->getEvents());

        DB::select('SELECT 1');
        $this->assertFalse($laritor->shouldSendEvents());

        Laritor::addCustomEvent('included');
        $event = $laritor->getEvents(Laritor::CUSTOM_EVENT)[0];
        $this->assertSame('included', $event['name']);
        $this->assertSame(2, $event['order']);

        $laritor->addCustomLog('internal', 'info', 'included');
        $this->assertTrue($laritor->shouldSendEvents());
    }

    public function test_queries_inside_a_filter_do_not_recurse_or_replace_dispatchers(): void
    {
        $override = new class extends DefaultOverride {
            public int $calls = 0;

            public function recordQueryBindings($query, $duration, $path): bool
            {
                $this->calls++;
                DB::select('SELECT 2');

                return false;
            }
        };
        $this->app->instance(LaritorOverride::class, $override);
        $laritor = app(Laritor::class);
        $laritor->reset();
        $eventDispatcher = Event::getFacadeRoot();
        $modelDispatcher = Model::getEventDispatcher();

        DB::select('SELECT 1');

        $this->assertSame(1, $override->calls);
        $this->assertCount(1, $laritor->getEvents(QueryRecorder::$eventType));
        $this->assertNull($laritor->getEvents(QueryRecorder::$eventType)[0]['bindings']);
        $this->assertSame($eventDispatcher, Event::getFacadeRoot());
        $this->assertSame($modelDispatcher, Model::getEventDispatcher());
        $this->assertFalse(Laritor::isRecordingPaused());
    }

    public function test_quiet_model_operations_remain_quiet_and_normal_saves_still_fire_events(): void
    {
        Schema::create('recording_test_models', function ($table) {
            $table->id();
            $table->string('name');
        });

        $fired = [];
        foreach (['saving', 'creating', 'created', 'updating', 'updated', 'saved'] as $event) {
            RecordingTestModel::$event(function () use ($event, &$fired) {
                $fired[] = $event;
            });
        }

        $model = new RecordingTestModel(['name' => 'first']);
        $model->saveQuietly();
        $model->updateQuietly(['name' => 'updated']);
        RecordingTestModel::withoutEvents(function () {
            $dispatcher = Model::getEventDispatcher();
            RecordingTestModel::query()->create(['name' => 'quiet']);
            $this->assertSame($dispatcher, Model::getEventDispatcher());
        });

        $this->assertSame([], $fired);

        Laritor::withoutRecording(fn () => RecordingTestModel::query()->create(['name' => 'normal']));
        $this->assertSame(['saving', 'creating', 'created', 'saved'], $fired);

        $fired = [];
        $model->update(['name' => 'normal update']);
        $this->assertSame(['saving', 'updating', 'updated', 'saved'], $fired);
    }

    public function test_filters_preserve_an_unset_model_dispatcher_and_their_fallback(): void
    {
        $eventDispatcher = Event::getFacadeRoot();
        $modelDispatcher = Model::getEventDispatcher();
        Model::unsetEventDispatcher();

        try {
            $this->assertSame('result', FilterHelper::recordEvent(fn () => 'result'));
            $this->assertFalse(FilterHelper::recordEvent(function () {
                throw new \RuntimeException('filter failed');
            }, false));
            $this->assertNull(Model::getEventDispatcher());
            $this->assertSame($eventDispatcher, Event::getFacadeRoot());
            $this->assertFalse(Laritor::isRecordingPaused());
        } finally {
            Model::setEventDispatcher($modelDispatcher);
        }
    }

    public function test_sending_events_pauses_recording_but_dispatches_application_events(): void
    {
        $laritor = app(Laritor::class);
        $laritor->reset();
        Laritor::addCustomEvent('included');
        $sending = 0;
        $eventDispatcher = Event::getFacadeRoot();

        Event::listen(RequestSending::class, function () use (&$sending) {
            $sending++;
            $this->assertTrue(Laritor::isRecordingPaused());
            DB::select('SELECT 1');
            Laritor::addCustomEvent('excluded');
        });

        Model::withoutEvents(function () use ($laritor) {
            $dispatcher = Model::getEventDispatcher();
            $laritor->sendEvents();
            $this->assertSame($dispatcher, Model::getEventDispatcher());
        });

        $payload = json_decode(file_get_contents(__DIR__.'/payloads/events.json'), true);
        $this->assertSame(1, $sending);
        $this->assertSame([Laritor::CUSTOM_EVENT], array_keys($payload['events']));
        $this->assertCount(1, $payload['events'][Laritor::CUSTOM_EVENT]);
        $this->assertSame([], $laritor->getEvents());
        $this->assertSame($eventDispatcher, Event::getFacadeRoot());
        $this->assertFalse(Laritor::isRecordingPaused());
    }
}

class RecordingTestModel extends Model
{
    public $timestamps = false;

    protected $guarded = [];
}
