<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Extension;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleCallbackHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function false_when_filter_skips_schedule()
    {
        $context = self::createBuilder(function(Schedule $schedule) {
            $schedule->when('boolean value', false);
        })->run();

        $this->assertFalse($context->hasRun());
        $this->assertTrue($context->isSkipped());
        $this->assertSame('boolean value', $context->getSkipReason());
    }

    /**
     * @test
     */
    public function callback_returning_false_when_filter_skips_schedule()
    {
        $context = self::createBuilder(function(Schedule $schedule) {
            $schedule->when('callback value', function() { return false; });
        })->run();

        $this->assertFalse($context->hasRun());
        $this->assertTrue($context->isSkipped());
        $this->assertSame('callback value', $context->getSkipReason());
    }

    /**
     * @test
     */
    public function true_when_filter_allows_schedule_to_run()
    {
        $context = self::createBuilder(function(Schedule $schedule) {
            $schedule->when('boolean value', true);
        })->run();

        $this->assertTrue($context->hasRun());
        $this->assertFalse($context->isSkipped());
    }

    /**
     * @test
     */
    public function callback_returning_true_when_filter_allows_schedule_to_run()
    {
        $context = self::createBuilder(function(Schedule $schedule) {
            $schedule->when('callback value', function() { return true; });
        })->run();

        $this->assertTrue($context->hasRun());
        $this->assertFalse($context->isSkipped());
    }

    /**
     * @test
     */
    public function true_skip_filter_skips_schedule()
    {
        $context = self::createBuilder(function(Schedule $schedule) {
            $schedule->skip('boolean value', true);
        })->run();

        $this->assertFalse($context->hasRun());
        $this->assertTrue($context->isSkipped());
        $this->assertSame('boolean value', $context->getSkipReason());
    }

    /**
     * @test
     */
    public function callback_returning_true_skip_filter_skips_schedule()
    {
        $context = self::createBuilder(function(Schedule $schedule) {
            $schedule->skip('callback value', function() { return true; });
        })->run();

        $this->assertFalse($context->hasRun());
        $this->assertTrue($context->isSkipped());
        $this->assertSame('callback value', $context->getSkipReason());
    }

    /**
     * @test
     */
    public function false_skip_filter_allows_schedule_to_run()
    {
        $context = self::createBuilder(function(Schedule $schedule) {
            $schedule->skip('boolean value', false);
        })->run();

        $this->assertTrue($context->hasRun());
        $this->assertFalse($context->isSkipped());
    }

    /**
     * @test
     */
    public function callback_returning_false_skip_filter_allows_schedule_to_run()
    {
        $context = self::createBuilder(function(Schedule $schedule) {
            $schedule->skip('callback value', function() { return false; });
        })->run();

        $this->assertTrue($context->hasRun());
        $this->assertFalse($context->isSkipped());
    }

    /**
     * @test
     */
    public function no_due_tasks_calls_runs_proper_callbacks()
    {
        $calls = [];

        self::createBuilder(function(Schedule $schedule) use (&$calls) {
            $schedule->filter(function() use (&$calls) { $calls[] = 'filter'; });
            $schedule->before(function() use (&$calls) { $calls[] = 'before'; });
            $schedule->after(function() use (&$calls) { $calls[] = 'after'; });
            $schedule->then(function() use (&$calls) { $calls[] = 'then'; });
            $schedule->onSuccess(function() use (&$calls) { $calls[] = 'onSuccess'; });
            $schedule->onFailure(function() use (&$calls) { $calls[] = 'onFailure'; });
        })->run();

        $this->assertSame([
            'filter',
            'before',
            'after',
            'then',
            'onSuccess',
        ], $calls);
    }

    /**
     * @test
     */
    public function all_successful_tasks_calls_runs_proper_callbacks()
    {
        $calls = [];

        self::createBuilder(function(Schedule $schedule) use (&$calls) {
            $schedule->filter(function() use (&$calls) { $calls[] = 'filter'; });
            $schedule->before(function() use (&$calls) { $calls[] = 'before'; });
            $schedule->after(function() use (&$calls) { $calls[] = 'after'; });
            $schedule->then(function() use (&$calls) { $calls[] = 'then'; });
            $schedule->onSuccess(function() use (&$calls) { $calls[] = 'onSuccess'; });
            $schedule->onFailure(function() use (&$calls) { $calls[] = 'onFailure'; });
        })->addTask(MockTask::success())->run();

        $this->assertSame([
            'filter',
            'before',
            'after',
            'then',
            'onSuccess',
        ], $calls);
    }

    /**
     * @test
     */
    public function single_failed_task_calls_runs_proper_callbacks()
    {
        $calls = [];

        self::createBuilder(function(Schedule $schedule) use (&$calls) {
            $schedule->filter(function() use (&$calls) { $calls[] = 'filter'; });
            $schedule->before(function() use (&$calls) { $calls[] = 'before'; });
            $schedule->after(function() use (&$calls) { $calls[] = 'after'; });
            $schedule->then(function() use (&$calls) { $calls[] = 'then'; });
            $schedule->onSuccess(function() use (&$calls) { $calls[] = 'onSuccess'; });
            $schedule->onFailure(function() use (&$calls) { $calls[] = 'onFailure'; });
        })->addTask(MockTask::success())->addTask(MockTask::failure())->run();

        $this->assertSame([
            'filter',
            'before',
            'after',
            'then',
            'onFailure',
        ], $calls);
    }

    private static function createBuilder(callable $builder): MockScheduleBuilder
    {
        return (new MockScheduleBuilder())
            ->addBuilder(new class($builder) implements ScheduleBuilder {
                private $builder;

                public function __construct(callable $builder)
                {
                    $this->builder = $builder;
                }

                public function buildSchedule(Schedule $schedule): void
                {
                    ($this->builder)($schedule);
                }
            })
        ;
    }
}
