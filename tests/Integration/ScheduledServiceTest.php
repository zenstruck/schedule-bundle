<?php

namespace Zenstruck\ScheduleBundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\Kernel;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunner;
use Zenstruck\ScheduleBundle\Schedule\Task\CallbackTask;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;
use Zenstruck\ScheduleBundle\Tests\Fixture\ScheduledService;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @requires PHP 8
 */
final class ScheduledServiceTest extends KernelTestCase
{
    protected function setUp(): void
    {
        if (Kernel::VERSION_ID < 50400) {
            $this->markTestSkipped('Not available before Symfony 5.4.');
        }
    }

    /**
     * @test
     */
    public function registers_scheduled_services(): void
    {
        $tasks = self::getContainer()->get(ScheduleRunner::class)->buildSchedule()->all();

        $this->assertCount(6, $tasks);

        $this->assertInstanceOf(CallbackTask::class, $tasks[0]);
        $this->assertSame(\sprintf('(callable) %s::__invoke()', ScheduledService::class), $tasks[0]->getDescription());
        $this->assertSame('@daily', (string) $tasks[0]->getExpression());

        $this->assertInstanceOf(CallbackTask::class, $tasks[1]);
        $this->assertSame('custom description', $tasks[1]->getDescription());
        $this->assertSame('@weekly', (string) $tasks[1]->getExpression());

        $this->assertInstanceOf(CallbackTask::class, $tasks[2]);
        $this->assertSame(\sprintf('(callable) %s::someMethod()', ScheduledService::class), $tasks[2]->getDescription());
        $this->assertSame('@monthly', (string) $tasks[2]->getExpression());

        $this->assertInstanceOf(CommandTask::class, $tasks[3]);
        $this->assertSame('my:command', $tasks[3]->getDescription());
        $this->assertSame('@daily', (string) $tasks[3]->getExpression());
        $this->assertSame('', $tasks[3]->getArguments());

        $this->assertInstanceOf(CommandTask::class, $tasks[4]);
        $this->assertSame('run my command', $tasks[4]->getDescription());
        $this->assertSame('@weekly', (string) $tasks[4]->getExpression());
        $this->assertSame('', $tasks[4]->getArguments());

        $this->assertInstanceOf(CommandTask::class, $tasks[5]);
        $this->assertSame('my:command', $tasks[5]->getDescription());
        $this->assertSame('@monthly', (string) $tasks[5]->getExpression());
        $this->assertSame('-vv --no-interaction', $tasks[5]->getArguments());
    }
}
