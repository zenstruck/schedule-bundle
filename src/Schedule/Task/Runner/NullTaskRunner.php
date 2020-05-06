<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task\Runner;

use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\NullTask;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NullTaskRunner implements TaskRunner
{
    /**
     * @param NullTask|Task $task
     */
    public function __invoke(Task $task): Result
    {
        return Result::successful($task);
    }

    public function supports(Task $task): bool
    {
        return $task instanceof NullTask;
    }
}
