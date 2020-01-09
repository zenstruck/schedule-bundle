<?php

namespace Zenstruck\ScheduleBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\SingleServerHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SingleServerTest extends TestCase
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
}
