<?php

namespace Zenstruck\ScheduleBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\EventListener\ConfigureScheduleSubscriber;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ConfigureScheduleTest extends TestCase
{
    /**
     * @test
     */
    public function can_configure_schedule_with_event()
    {
        $extension = new class() implements Extension {
            public function __toString(): string
            {
                return 'my extension';
            }
        };

        $schedule = (new MockScheduleBuilder())
            ->addSubscriber(new ConfigureScheduleSubscriber([$extension]))
            ->getRunner()
            ->buildSchedule()
        ;

        $this->assertSame($extension, $schedule->getExtensions()[0]);
    }
}
