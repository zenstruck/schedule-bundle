<?php

namespace Zenstruck\ScheduleBundle\Schedule;

use Zenstruck\ScheduleBundle\Schedule;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void;
}
