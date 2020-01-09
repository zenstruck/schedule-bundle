<?php

namespace Zenstruck\ScheduleBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\WithoutOverlappingHandler;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class WithoutOverlappingTest extends TestCase
{
    /**
     * @test
     */
    public function can_use_handler_to_configure_lock_factory()
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
