<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Exception;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SkipTaskTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_skipped_result()
    {
        $task = new MockTask();
        $exception = new SkipTask('some reason');
        $result = $exception->createResult($task);

        $this->assertTrue($result->isSkipped());
        $this->assertSame($task, $result->getTask());
        $this->assertSame('some reason', $result->getDescription());
    }
}
