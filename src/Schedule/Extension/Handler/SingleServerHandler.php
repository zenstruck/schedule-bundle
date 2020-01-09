<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Symfony\Component\Lock\LockFactory;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SingleServerHandler extends ExtensionHandler
{
    private $lockFactory;

    public function __construct(LockFactory $lockFactory)
    {
        $this->lockFactory = $lockFactory;
    }

    /**
     * @param SingleServerExtension $extension
     */
    public function filterSchedule(BeforeScheduleEvent $event, Extension $extension): void
    {
        $extension->aquireScheduleLock($this->lockFactory, $event->getSchedule(), $event->getStartTime());
    }

    /**
     * @param SingleServerExtension $extension
     */
    public function filterTask(BeforeTaskEvent $event, Extension $extension): void
    {
        $extension->aquireTaskLock($this->lockFactory, $event->getTask(), $event->getScheduleStartTime());
    }

    public function supports(Extension $extension): bool
    {
        return $extension instanceof SingleServerExtension;
    }
}
