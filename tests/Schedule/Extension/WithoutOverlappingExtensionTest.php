<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Extension;

use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\WithoutOverlappingHandler;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class WithoutOverlappingExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function task_cannot_be_run_if_locked()
    {
        $handler = new WithoutOverlappingHandler();
        $runContext1 = new TaskRunContext(new ScheduleRunContext(new Schedule()), MockTask::success('task')->withoutOverlapping());
        $runContext2 = new TaskRunContext(new ScheduleRunContext(new Schedule()), MockTask::success('task')->withoutOverlapping());

        $handler->filterTask($runContext1, $runContext1->getTask()->getExtensions()[0]);

        $this->expectException(SkipTask::class);
        $this->expectExceptionMessage('Task running in another process.');

        $handler->filterTask($runContext2, $runContext2->getTask()->getExtensions()[0]);
    }

    /**
     * @test
     */
    public function lock_is_released_after_task()
    {
        $logger = new TestLogger();
        $lockFactory = new LockFactory(new FlockStore());
        $lockFactory->setLogger($logger);

        (new MockScheduleBuilder())
            ->addHandler(new WithoutOverlappingHandler($lockFactory))
            ->addTask((new MockTask())->withoutOverlapping())
            ->run()
        ;

        $this->assertTrue($logger->hasInfoThatContains('Successfully acquired'));
        $this->assertTrue($logger->hasInfoThatContains('Expiration defined'));
    }
}
