<?php

namespace Zenstruck\ScheduleBundle\Schedule;

use Zenstruck\ScheduleBundle\Schedule;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleRunContext
{
    private $schedule;
    private $startTime;
    private $dueTasks;

    public function __construct(Schedule $schedule, Task ...$dueTasks)
    {
        $this->schedule = $schedule;
        $this->startTime = \time();
        $this->dueTasks = $dueTasks;
    }

    public function getScheduleId(): string
    {
        return $this->schedule->getId();
    }

    /**
     * @return Task[]
     */
    public function allTasks(): array
    {
        return $this->schedule->all();
    }

    /**
     * @return Task[]
     */
    public function dueTasks(): array
    {
        return $this->dueTasks;
    }

    public function startTime(): int
    {
        return $this->startTime;
    }

    /**
     * @return Extension[]
     */
    public function scheduleExtensions(): array
    {
        return $this->schedule->getExtensions();
    }
}
