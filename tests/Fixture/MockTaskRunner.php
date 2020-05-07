<?php

namespace Zenstruck\ScheduleBundle\Tests\Fixture;

use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MockTaskRunner implements TaskRunner
{
    /**
     * @param MockTask|Task $task
     */
    public function __invoke(Task $task): Result
    {
        return $task->getResult();
    }

    public function supports(Task $task): bool
    {
        return $task instanceof MockTask;
    }
}
