<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
        $context = self::createBuilder(static function(Schedule $schedule) {
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
        $context = self::createBuilder(static function(Schedule $schedule) {
            $schedule->when('callback value', static fn() => false);
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
        $context = self::createBuilder(static function(Schedule $schedule) {
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
        $context = self::createBuilder(static function(Schedule $schedule) {
            $schedule->when('callback value', static fn() => true);
        })->run();

        $this->assertTrue($context->hasRun());
        $this->assertFalse($context->isSkipped());
    }

    /**
     * @test
     */
    public function true_skip_filter_skips_schedule()
    {
        $context = self::createBuilder(static function(Schedule $schedule) {
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
        $context = self::createBuilder(static function(Schedule $schedule) {
            $schedule->skip('callback value', static fn() => true);
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
        $context = self::createBuilder(static function(Schedule $schedule) {
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
        $context = self::createBuilder(static function(Schedule $schedule) {
            $schedule->skip('callback value', static fn() => false);
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

        self::createBuilder(static function(Schedule $schedule) use (&$calls) {
            $schedule->filter(static function() use (&$calls) { $calls[] = 'filter'; });
            $schedule->before(static function() use (&$calls) { $calls[] = 'before'; });
            $schedule->after(static function() use (&$calls) { $calls[] = 'after'; });
            $schedule->then(static function() use (&$calls) { $calls[] = 'then'; });
            $schedule->onSuccess(static function() use (&$calls) { $calls[] = 'onSuccess'; });
            $schedule->onFailure(static function() use (&$calls) { $calls[] = 'onFailure'; });
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

        self::createBuilder(static function(Schedule $schedule) use (&$calls) {
            $schedule->filter(static function() use (&$calls) { $calls[] = 'filter'; });
            $schedule->before(static function() use (&$calls) { $calls[] = 'before'; });
            $schedule->after(static function() use (&$calls) { $calls[] = 'after'; });
            $schedule->then(static function() use (&$calls) { $calls[] = 'then'; });
            $schedule->onSuccess(static function() use (&$calls) { $calls[] = 'onSuccess'; });
            $schedule->onFailure(static function() use (&$calls) { $calls[] = 'onFailure'; });
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

        self::createBuilder(static function(Schedule $schedule) use (&$calls) {
            $schedule->filter(static function() use (&$calls) { $calls[] = 'filter'; });
            $schedule->before(static function() use (&$calls) { $calls[] = 'before'; });
            $schedule->after(static function() use (&$calls) { $calls[] = 'after'; });
            $schedule->then(static function() use (&$calls) { $calls[] = 'then'; });
            $schedule->onSuccess(static function() use (&$calls) { $calls[] = 'onSuccess'; });
            $schedule->onFailure(static function() use (&$calls) { $calls[] = 'onFailure'; });
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
