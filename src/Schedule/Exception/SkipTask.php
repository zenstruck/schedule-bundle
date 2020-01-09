<?php

namespace Zenstruck\ScheduleBundle\Schedule\Exception;

use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SkipTask extends \DomainException
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }

    public function createResult(Task $task): Result
    {
        return Result::skipped($task, $this->getMessage());
    }
}
