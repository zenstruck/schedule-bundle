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
    private $scheduleStartTime;

    public function __construct(BeforeScheduleEvent $beforeScheduleEvent, Task $task)
    {
        parent::__construct($beforeScheduleEvent->getSchedule());

        $this->task = $task;
        $this->startTime = \time();
        $this->scheduleStartTime = $beforeScheduleEvent->getStartTime();
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getStartTime(): int
    {
        return $this->startTime;
    }

    public function getScheduleStartTime(): int
    {
        return $this->scheduleStartTime;
    }
}
