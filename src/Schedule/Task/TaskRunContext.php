<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Zenstruck\ScheduleBundle\Schedule\RunContext;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TaskRunContext extends RunContext
{
    private $scheduleRunContext;
    private $task;

    private $result;

    public function __construct(ScheduleRunContext $scheduleRunContext, Task $task)
    {
        $this->scheduleRunContext = $scheduleRunContext;
        $this->task = $task;

        parent::__construct();
    }

    public function __toString(): string
    {
        return (string) $this->task();
    }

    public function scheduleRunContext(): ScheduleRunContext
    {
        return $this->scheduleRunContext;
    }

    public function task(): Task
    {
        return $this->task;
    }

    /**
     * @throws \LogicException if has not yet run
     */
    public function result(): Result
    {
        $this->ensureHasRun();

        return $this->result;
    }

    public function setResult(Result $result): void
    {
        $resultTask = $result->getTask();

        if ($resultTask->getId() !== $this->task()->getId()) {
            throw new \LogicException("The result's task ({$resultTask}) does not match the context's task ({$this->task()}).");
        }

        $this->markAsRun(\memory_get_usage(true));

        $this->result = $result;
    }

    /**
     * @throws \LogicException if has not yet run
     */
    public function isSuccessful(): bool
    {
        return $this->result()->isSuccessful();
    }

    /**
     * @throws \LogicException if has not yet run
     */
    public function isFailure(): bool
    {
        return $this->result()->isFailure();
    }
}
