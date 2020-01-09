<?php

namespace Zenstruck\ScheduleBundle\Schedule;

use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface SelfSchedulingCommand
{
    public function schedule(CommandTask $task): void;
}
