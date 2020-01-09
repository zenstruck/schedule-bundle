<?php

namespace Zenstruck\ScheduleBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Extension\EnvironmentExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\EnvironmentHandler;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EnvironmentTest extends TestCase
{
    /**
     * @test
     */
    public function does_not_run_when_environment_does_not_match()
    {
        $event = (new MockScheduleBuilder())
            ->addHandler(new EnvironmentHandler('dev'))
            ->addExtension(new EnvironmentExtension(['prod', 'stage']))
            ->run()
        ;

        $this->assertTrue($event->isSkipped());
        $this->assertTrue($event->isSuccessful());
        $this->assertSame('Schedule configured not to run in [dev] environment (only [prod, stage]).', $event->getSkipReason());
    }

    /**
     * @test
     */
    public function allows_schedule_to_run_if_environment_matches()
    {
        $event = (new MockScheduleBuilder())
            ->addHandler(new EnvironmentHandler('prod'))
            ->addExtension(new EnvironmentExtension(['prod', 'stage']))
            ->run()
        ;

        $this->assertFalse($event->isSkipped());
    }
}
