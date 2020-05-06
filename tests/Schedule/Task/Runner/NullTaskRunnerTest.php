<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task\Runner;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Task\NullTask;
use Zenstruck\ScheduleBundle\Schedule\Task\Runner\NullTaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NullTaskRunnerTest extends TestCase
{
    /**
     * @test
     */
    public function always_is_successful()
    {
        $runner = new NullTaskRunner();
        $task = new NullTask('my task');

        $this->assertTrue($runner($task)->isSuccessful());
    }

    /**
     * @test
     */
    public function supports_null_task()
    {
        $this->assertTrue((new NullTaskRunner())->supports(new NullTask('my task')));
    }
}
