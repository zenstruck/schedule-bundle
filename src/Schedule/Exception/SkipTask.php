<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
