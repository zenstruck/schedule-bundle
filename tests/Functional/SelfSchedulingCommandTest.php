<?php

namespace Zenstruck\ScheduleBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Zenstruck\ScheduleBundle\EventListener\SelfSchedulingSubscriber;
use Zenstruck\ScheduleBundle\Schedule\SelfSchedulingCommand;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SelfSchedulingCommandTest extends TestCase
{
    /**
     * @test
     */
    public function commands_can_self_schedule()
    {
        $command = new class() extends Command implements SelfSchedulingCommand {
            protected static $defaultName = 'my:command';

            public function schedule(CommandTask $task): void
            {
            }
        };

        $tasks = (new MockScheduleBuilder())
            ->addSubscriber(new SelfSchedulingSubscriber([$command]))
            ->getRunner()
            ->buildSchedule()
            ->all()
        ;

        $this->assertInstanceOf(CommandTask::class, $tasks[0]);
        $this->assertSame('my:command', (string) $tasks[0]);
    }

    /**
     * @test
     */
    public function throws_exception_if_not_a_command()
    {
        $command = new class() implements SelfSchedulingCommand {
            public function schedule(CommandTask $task): void
            {
            }
        };

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/is not a console command/');

        (new MockScheduleBuilder())
            ->addSubscriber(new SelfSchedulingSubscriber([$command]))
            ->run()
        ;
    }
}
