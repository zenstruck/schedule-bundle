<?php

namespace Zenstruck\ScheduleBundle\Event;

use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BeforeTaskEvent extends ScheduleEvent
{
    private $task;
    private $startTime;

    public function __construct(BeforeScheduleEvent $beforeScheduleEvent, Task $task)
    {
        $scheduleRunContext = $beforeScheduleEvent->getScheduleRunContext();

        parent::__construct($scheduleRunContext);

        $this->task = $task;
        $this->startTime = \time();
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getStartTime(): int
    {
        return $this->startTime;
    }
}
