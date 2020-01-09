<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Task\CompoundTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CompoundTaskTest extends TestCase
{
    /**
     * @test
     */
    public function cannot_nest_compound_tasks()
    {
        $task = new CompoundTask();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot nest compound tasks.');

        $task->add(new CompoundTask());
    }
}
