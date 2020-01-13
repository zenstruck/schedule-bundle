<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Task\NullTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class NullTaskTest extends TestCase
{
    /**
     * @test
     */
    public function always_is_successful()
    {
        $this->assertTrue((new NullTask('my task'))()->isSuccessful());
    }
}
