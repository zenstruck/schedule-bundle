<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task\Runner;

use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\SelfRunningTask;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SelfRunningTaskRunner implements TaskRunner
{
    /**
     * @param SelfRunningTask $task
     */
    public function __invoke(Task $task): Result
    {
        return $task();
    }

    public function supports(Task $task): bool
    {
        return $task instanceof SelfRunningTask;
    }
}
