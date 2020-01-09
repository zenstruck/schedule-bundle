<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface SelfRunningTask
{
    public function __invoke(): Result;
}
