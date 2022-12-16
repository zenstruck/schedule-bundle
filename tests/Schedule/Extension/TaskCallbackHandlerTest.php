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
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TaskCallbackHandlerTest extends TestCase
{
    /**
     * @test
     */
    public function false_when_filter_skips_task()
    {
        $task = MockTask::success()->when('boolean value', false);

        $context = self::createRunContext($task);

        $this->assertTrue($context->hasRun());
        $this->assertCount(0, $context->getRun());
        $this->assertCount(1, $skipped = $context->getSkipped());
        $this->assertSame('boolean value', $skipped[0]->getDescription());
    }

    /**
     * @test
     */
    public function callback_returning_false_when_filter_skips_task()
    {
        $task = MockTask::success()->when('callback value', fn() => false);

        $context = self::createRunContext($task);

        $this->assertTrue($context->hasRun());
        $this->assertCount(0, $context->getRun());
        $this->assertCount(1, $skipped = $context->getSkipped());
        $this->assertSame('callback value', $skipped[0]->getDescription());
    }

    /**
     * @test
     */
    public function true_when_filter_allows_task_to_run()
    {
        $task = MockTask::success()->when('boolean value', true);

        $context = self::createRunContext($task);

        $this->assertTrue($context->hasRun());
        $this->assertCount(1, $context->getRun());
        $this->assertCount(0, $context->getSkipped());
    }

    /**
     * @test
     */
    public function callback_returning_true_when_filter_allows_task_to_run()
    {
        $task = MockTask::success()->when('callback value', fn() => true);

        $context = self::createRunContext($task);

        $this->assertTrue($context->hasRun());
        $this->assertCount(1, $context->getRun());
        $this->assertCount(0, $context->getSkipped());
    }

    /**
     * @test
     */
    public function true_skip_filter_skips_task()
    {
        $task = MockTask::success()->skip('boolean value', true);

        $context = self::createRunContext($task);

        $this->assertTrue($context->hasRun());
        $this->assertCount(0, $context->getRun());
        $this->assertCount(1, $skipped = $context->getSkipped());
        $this->assertSame('boolean value', $skipped[0]->getDescription());
    }

    /**
     * @test
     */
    public function callback_returning_true_skip_filter_skips_task()
    {
        $task = MockTask::success()->skip('callback value', fn() => true);

        $context = self::createRunContext($task);

        $this->assertTrue($context->hasRun());
        $this->assertCount(0, $context->getRun());
        $this->assertCount(1, $skipped = $context->getSkipped());
        $this->assertSame('callback value', $skipped[0]->getDescription());
    }

    /**
     * @test
     */
    public function false_skip_filter_allows_task_to_run()
    {
        $task = MockTask::success()->skip('boolean value', false);

        $context = self::createRunContext($task);

        $this->assertTrue($context->hasRun());
        $this->assertCount(1, $context->getRun());
        $this->assertCount(0, $context->getSkipped());
    }

    /**
     * @test
     */
    public function callback_returning_false_skip_filter_allows_task_to_run()
    {
        $task = MockTask::success()->skip('callback value', fn() => false);

        $context = self::createRunContext($task);

        $this->assertTrue($context->hasRun());
        $this->assertCount(1, $context->getRun());
        $this->assertCount(0, $context->getSkipped());
    }

    /**
     * @test
     */
    public function successful_task_calls_proper_callback_extensions()
    {
        $calls = [];
        $task = MockTask::success()
            ->filter(function() use (&$calls) { $calls[] = 'filter'; })
            ->before(function() use (&$calls) { $calls[] = 'before'; })
            ->after(function() use (&$calls) { $calls[] = 'after'; })
            ->then(function() use (&$calls) { $calls[] = 'then'; })
            ->onSuccess(function() use (&$calls) { $calls[] = 'onSuccess'; })
            ->onFailure(function() use (&$calls) { $calls[] = 'onFailure'; })
        ;

        self::createRunContext($task);

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
    public function failed_task_calls_proper_callback_extensions()
    {
        $calls = [];
        $task = MockTask::failure()
            ->filter(function() use (&$calls) { $calls[] = 'filter'; })
            ->before(function() use (&$calls) { $calls[] = 'before'; })
            ->after(function() use (&$calls) { $calls[] = 'after'; })
            ->then(function() use (&$calls) { $calls[] = 'then'; })
            ->onSuccess(function() use (&$calls) { $calls[] = 'onSuccess'; })
            ->onFailure(function() use (&$calls) { $calls[] = 'onFailure'; })
        ;

        self::createRunContext($task);

        $this->assertSame([
            'filter',
            'before',
            'after',
            'then',
            'onFailure',
        ], $calls);
    }

    /**
     * @test
     */
    public function skipped_task_calls_proper_callback_extensions()
    {
        $calls = [];
        $task = MockTask::skipped()
            ->filter(function() use (&$calls) { $calls[] = 'filter'; })
            ->before(function() use (&$calls) { $calls[] = 'before'; })
            ->after(function() use (&$calls) { $calls[] = 'after'; })
            ->then(function() use (&$calls) { $calls[] = 'then'; })
            ->onSuccess(function() use (&$calls) { $calls[] = 'onSuccess'; })
            ->onFailure(function() use (&$calls) { $calls[] = 'onFailure'; })
        ;

        self::createRunContext($task);

        $this->assertSame([
            'filter',
            'before',
            'after',
            'then',
        ], $calls);
    }

    private static function createRunContext(Task $task): ScheduleRunContext
    {
        return (new MockScheduleBuilder())
            ->addTask($task)
            ->run()
        ;
    }
}
