<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TaskRunContextTest extends TestCase
{
    /**
     * @test
     */
    public function cannot_access_result_if_has_not_run()
    {
        $context = new TaskRunContext(new ScheduleRunContext(new Schedule()), new MockTask('my task'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"MockTask: my task" has not yet run.');

        $context->getResult();
    }

    /**
     * @test
     */
    public function cannot_set_result_for_different_task()
    {
        $context = new TaskRunContext(new ScheduleRunContext(new Schedule()), new MockTask('my task'));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage("The result's task (MockTask: another task) does not match the context's task (MockTask: my task).");

        $context->setResult(Result::successful(new MockTask('another task')));
    }
}
