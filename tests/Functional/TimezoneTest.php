<?php

namespace Zenstruck\ScheduleBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\EventListener\TimezoneSubscriber;
use Zenstruck\ScheduleBundle\Schedule\Task\CompoundTask;
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
            ->addTask((new MockTask())->timezone('America/Edmonton'))
            ->addTask((new CompoundTask())
                ->add(new MockTask())
                ->add((new MockTask())->timezone(new \DateTimeZone('America/Edmonton')))
            )
            ->addSubscriber(new TimezoneSubscriber('America/Toronto'))
            ->getRunner()
            ->buildSchedule()
            ->all()
        ;

        $this->assertSame('America/Toronto', $tasks[0]->getTimezone()->getName());
        $this->assertSame('America/Edmonton', $tasks[1]->getTimezone()->getName());
        $this->assertSame('America/Toronto', $tasks[2]->getTimezone()->getName());
        $this->assertSame('America/Edmonton', $tasks[3]->getTimezone()->getName());
    }
}
