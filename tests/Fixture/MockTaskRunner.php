<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests\Fixture;

use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MockTaskRunner implements TaskRunner
{
    /**
     * @param MockTask|Task $task
     */
    public function __invoke(Task $task): Result
    {
        return $task->getResult();
    }

    public function supports(Task $task): bool
    {
        return $task instanceof MockTask;
    }
}
