<?php

namespace Zenstruck\ScheduleBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ScheduleEvent extends Event
{
    private $scheduleRunContext;

    public function __construct(ScheduleRunContext $scheduleRunContext)
    {
        $this->scheduleRunContext = $scheduleRunContext;
    }

    final public function getScheduleRunContext(): ScheduleRunContext
    {
        return $this->scheduleRunContext;
    }
}
