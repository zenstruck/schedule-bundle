<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task\Runner;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\Runner\SelfRunningTaskRunner;
use Zenstruck\ScheduleBundle\Schedule\Task\SelfRunningTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SelfRunningTaskRunnerTest extends TestCase
{
    /**
     * @test
     */
    public function supports_and_invokes_class()
    {
        $task = new class('my task') extends Task implements SelfRunningTask {
            public function __invoke(): Result
            {
                return Result::successful($this);
            }
        };

        $runner = new SelfRunningTaskRunner();

        $this->assertTrue($runner->supports($task));
        $this->assertTrue($runner($task)->isSuccessful());
    }
}
