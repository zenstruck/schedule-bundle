<?php

namespace Zenstruck\ScheduleBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\EventListener\TimezoneSubscriber;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TimezoneTest extends TestCase
{
    /**
     * @test
     */
    public function can_set_a_default_timezone()
    {
        $tasks = (new MockScheduleBuilder())
            ->addTask(new MockTask())
            ->addTask((new MockTask())->timezone('America/New_York'))
            ->addSubscriber(new TimezoneSubscriber('UTC'))
            ->getRunner()
            ->buildSchedule()
            ->all()
        ;

        $this->assertSame('UTC', $tasks[0]->getTimezone()->getName());
        $this->assertSame('America/New_York', $tasks[1]->getTimezone()->getName());
    }
}
