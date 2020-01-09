<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface TaskRunner
{
    public function __invoke(Task $task): Result;

    public function supports(Task $task): bool;
}
