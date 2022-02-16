<?php

namespace Zenstruck\ScheduleBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ScheduleEvent extends Event
{
    /** @var ScheduleRunContext */
    private $runContext;

    final public function __construct(ScheduleRunContext $runContext)
    {
        $this->runContext = $runContext;
    }

    final public function runContext(): ScheduleRunContext
    {
        return $this->runContext;
    }
}
