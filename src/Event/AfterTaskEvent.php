<?php

namespace Zenstruck\ScheduleBundle\Event;

use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AfterTaskEvent extends ResultEvent
{
    private $result;

    public function __construct(BeforeTaskEvent $beforeTaskEvent, Result $result)
    {
        parent::__construct(
            $beforeTaskEvent->getScheduleRunContext(),
            \time() - $beforeTaskEvent->getStartTime(),
            \memory_get_usage(true)
        );

        $this->result = $result;
    }

    public function getResult(): Result
    {
        return $this->result;
    }

    public function getTask(): Task
    {
        return $this->result->getTask();
    }

    public function isSuccessful(): bool
    {
        return $this->result->isSuccessful();
    }

    public function isFailure(): bool
    {
        return $this->result->isFailure();
    }
}
