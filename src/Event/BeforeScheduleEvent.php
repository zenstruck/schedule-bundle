<?php

namespace Zenstruck\ScheduleBundle\Event;

use Zenstruck\ScheduleBundle\Schedule;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BeforeScheduleEvent extends ScheduleEvent
{
    private $startTime;

    public function __construct(Schedule $schedule)
    {
        parent::__construct($schedule);

        $this->startTime = \time();
    }

    public function getStartTime(): int
    {
        return $this->startTime;
    }
}
