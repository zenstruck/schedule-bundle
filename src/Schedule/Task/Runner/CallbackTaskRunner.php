<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Task\Runner;

use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\CallbackTask;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CallbackTaskRunner implements TaskRunner
{
    /**
     * @param CallbackTask $task
     */
    public function __invoke(Task $task): Result
    {
        $output = $task->getCallback()();

        return Result::successful($task, self::stringify($output));
    }

    public function supports(Task $task): bool
    {
        return $task instanceof CallbackTask;
    }

    /**
     * @param mixed $value
     */
    private static function stringify($value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (\is_scalar($value)) {
            return (string) $value;
        }

        if (\is_object($value) && \method_exists($value, '__toString')) {
            return $value;
        }

        if (\is_object($value)) {
            return '[object] '.$value::class;
        }

        return '('.\gettype($value).')';
    }
}
