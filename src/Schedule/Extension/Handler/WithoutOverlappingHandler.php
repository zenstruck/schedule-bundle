<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Symfony\Component\Lock\LockFactory;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\WithoutOverlappingExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class WithoutOverlappingHandler extends ExtensionHandler
{
    private $lockFactory;

    public function __construct(LockFactory $lockFactory)
    {
        $this->lockFactory = $lockFactory;
    }

    /**
     * @param WithoutOverlappingExtension $extension
     */
    public function filterTask(BeforeTaskEvent $event, Extension $extension): void
    {
        $extension->setLockFactory($this->lockFactory)->filterTask($event);
    }

    /**
     * @param WithoutOverlappingExtension $extension
     */
    public function afterTask(AfterTaskEvent $event, Extension $extension): void
    {
        $extension->setLockFactory($this->lockFactory)->afterTask($event);
    }

    public function supports(Extension $extension): bool
    {
        return $extension instanceof WithoutOverlappingExtension;
    }
}
