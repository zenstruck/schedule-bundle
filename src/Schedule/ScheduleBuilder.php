<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule;

use Zenstruck\ScheduleBundle\Schedule;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void;
}
