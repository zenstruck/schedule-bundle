<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Extension;

use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\SingleServerHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SingleServerExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function can_lock_schedule()
    {
        $logger = new TestLogger();
        $lockFactory = new LockFactory(new FlockStore());
        $lockFactory->setLogger($logger);

        (new MockScheduleBuilder())
            ->addHandler(new SingleServerHandler($lockFactory))
            ->addExtension(new SingleServerExtension())
            ->run()
        ;

        $this->assertTrue($logger->hasInfoThatContains('Successfully acquired'));
        $this->assertTrue($logger->hasInfoThatContains('Expiration defined'));
    }

    /**
     * @test
     */
    public function can_lock_task()
    {
        $logger = new TestLogger();
        $lockFactory = new LockFactory(new FlockStore());
        $lockFactory->setLogger($logger);

        (new MockScheduleBuilder())
            ->addHandler(new SingleServerHandler($lockFactory))
            ->addTask((new MockTask())->onSingleServer())
            ->run()
        ;

        $this->assertTrue($logger->hasInfoThatContains('Successfully acquired'));
        $this->assertTrue($logger->hasInfoThatContains('Expiration defined'));
    }

    /**
     * @test
     */
    public function provides_helpful_message_if_handler_not_configured()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('To use "onSingleServer" you must configure a lock factory (config path: "zenstruck_schedule.single_server_handler")');

        (new MockScheduleBuilder())
            ->addExtension(new SingleServerExtension())
            ->run()
        ;
    }

    /**
     * @test
     */
    public function skips_schedule_if_locked()
    {
        $schedule1 = new Schedule();
        $schedule1->addCommand('my:command');
        $schedule1->onSingleServer();
        $schedule2 = new Schedule();
        $schedule2->addCommand('my:command');
        $schedule2->onSingleServer();

        $handler = new SingleServerHandler(new LockFactory(new FlockStore()));
        $event1 = new BeforeScheduleEvent($schedule1->createRunContext());
        $event2 = new BeforeScheduleEvent($schedule2->createRunContext());

        $handler->filterSchedule($event1, $schedule1->getExtensions()[0]);

        $this->expectException(SkipSchedule::class);
        $this->expectExceptionMessage('Schedule running on another server.');

        $handler->filterSchedule($event2, $schedule2->getExtensions()[0]);
    }

    /**
     * @test
     */
    public function skips_task_if_locked()
    {
        $schedule1 = new Schedule();
        $task1 = $schedule1->addCommand('my:command')->onSingleServer();
        $schedule2 = new Schedule();
        $task2 = $schedule2->addCommand('my:command')->onSingleServer();

        $event1 = new BeforeTaskEvent(new BeforeScheduleEvent($schedule1->createRunContext()), $task1);
        $event2 = new BeforeTaskEvent(new BeforeScheduleEvent($schedule2->createRunContext()), $task2);

        $handler = new SingleServerHandler(new LockFactory(new FlockStore()));

        $handler->filterTask($event1, $task1->getExtensions()[0]);

        $this->expectException(SkipTask::class);
        $this->expectExceptionMessage('Task running on another server.');

        $handler->filterTask($event2, $task2->getExtensions()[0]);
    }
}
