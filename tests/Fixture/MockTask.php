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

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MockTask extends Task
{
    private $result;

    public function __construct(string $description = 'my task')
    {
        parent::__construct($description);
    }

    public function getResult(): Result
    {
        return $this->result;
    }

    public static function success(string $name = 'my task', ?string $output = null): self
    {
        $task = new self($name);
        $task->result = Result::successful($task, $output);

        return $task;
    }

    public static function failure(string $description = 'failure description', string $name = 'my task', ?string $output = null): self
    {
        $task = new self($name);
        $task->result = Result::failure($task, $description, $output);

        return $task;
    }

    public static function skipped(string $description = 'skip reason', string $name = 'my task')
    {
        $task = new self($name);
        $task->result = Result::skipped($task, $description);

        return $task;
    }

    public static function exception(\Throwable $e, string $name = 'my task', ?string $output = null): self
    {
        $task = new self($name);
        $task->result = Result::exception($task, $e, $output);

        return $task;
    }
}
