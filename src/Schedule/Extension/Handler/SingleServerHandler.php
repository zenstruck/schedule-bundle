<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Symfony\Component\Lock\LockFactory;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
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
        if (!$extension->aquireLock($this->lockFactory, $event->getSchedule()->getId(), $event->getStartTime())) {
            throw new SkipSchedule('Schedule running on another server.');
        }
    }

    /**
     * @param SingleServerExtension $extension
     */
    public function filterTask(BeforeTaskEvent $event, Extension $extension): void
    {
        if (!$extension->aquireLock($this->lockFactory, $event->getTask()->getId(), $event->getScheduleStartTime())) {
            throw new SkipTask('Task running on another server.');
        }
    }

    public function supports(Extension $extension): bool
    {
        return $extension instanceof SingleServerExtension;
    }
}
