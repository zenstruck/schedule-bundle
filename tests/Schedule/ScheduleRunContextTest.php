<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests\Schedule;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleRunContextTest extends TestCase
{
    private string $timezone;

    protected function setUp(): void
    {
        $this->timezone = \date_default_timezone_get();
    }

    protected function tearDown(): void
    {
        \date_default_timezone_set($this->timezone);
    }

    /**
     * @test
     */
    public function start_time_timezone(): void
    {
        \date_default_timezone_set('America/New_York');

        $context = new ScheduleRunContext(new Schedule());

        $this->assertSame('America/New_York', $context->getStartTime()->getTimezone()->getName());
    }

    /**
     * @test
     */
    public function cannot_access_results_if_has_not_run()
    {
        $context = new ScheduleRunContext(new Schedule());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"The Schedule" has not yet run.');

        $context->getResults();
    }

    /**
     * @test
     */
    public function set_results_count_mismatch()
    {
        $scheduleRunContext = new ScheduleRunContext(new Schedule(), new MockTask(), new MockTask());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The number of results (1) does not match the number of due tasks (2).');

        $taskRunContext = new TaskRunContext($scheduleRunContext, new MockTask());
        $taskRunContext->setResult(Result::successful(new MockTask()));

        $scheduleRunContext->setTaskRunContexts($taskRunContext);
    }
}
