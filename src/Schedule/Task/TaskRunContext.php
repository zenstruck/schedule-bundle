<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Zenstruck\ScheduleBundle\Schedule\RunContext;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TaskRunContext extends RunContext
{
    /** @var ScheduleRunContext */
    private $scheduleRunContext;

    /** @var Task */
    private $task;

    /** @var Task\Result */
    private $result;

    public function __construct(ScheduleRunContext $scheduleRunContext, Task $task)
    {
        $this->scheduleRunContext = $scheduleRunContext;
        $this->task = $task;

        parent::__construct();
    }

    public function __toString(): string
    {
        return (string) $this->getTask();
    }

    public function getScheduleRunContext(): ScheduleRunContext
    {
        return $this->scheduleRunContext;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    /**
     * @throws \LogicException if has not yet run
     */
    public function getResult(): Task\Result
    {
        $this->ensureHasRun();

        return $this->result;
    }

    public function setResult(Task\Result $result): void
    {
        $resultTask = $result->getTask();

        if ($resultTask->getId() !== $this->getTask()->getId()) {
            throw new \LogicException("The result's task ({$resultTask}) does not match the context's task ({$this->getTask()}).");
        }

        $this->markAsRun(\memory_get_usage(true));

        $this->result = $result;
    }

    /**
     * @throws \LogicException if has not yet run
     */
    public function isSuccessful(): bool
    {
        return $this->getResult()->isSuccessful();
    }

    /**
     * @throws \LogicException if has not yet run
     */
    public function isFailure(): bool
    {
        return $this->getResult()->isFailure();
    }
}
