<?php

namespace Zenstruck\ScheduleBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Zenstruck\ScheduleBundle\Schedule;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ScheduleEvent extends Event
{
    private $schedule;

    public function __construct(Schedule $schedule)
    {
        $this->schedule = $schedule;
    }

    final public function getSchedule(): Schedule
    {
        return $this->schedule;
    }
}
