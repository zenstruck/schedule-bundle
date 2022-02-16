<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Symfony\Component\Lock\LockFactory;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SingleServerHandler extends ExtensionHandler
{
    /** @var LockFactory */
    private $lockFactory;

    public function __construct(LockFactory $lockFactory)
    {
        $this->lockFactory = $lockFactory;
    }

    /**
     * @param SingleServerExtension $extension
     */
    public function filterSchedule(ScheduleRunContext $context, object $extension): void
    {
        if (!$extension->acquireLock($this->lockFactory, self::createMutex($context->getSchedule()->getId(), $context->getStartTime()))) {
            throw new SkipSchedule('Schedule running on another server.');
        }
    }

    /**
     * @param SingleServerExtension $extension
     */
    public function filterTask(TaskRunContext $context, object $extension): void
    {
        if (!$extension->acquireLock($this->lockFactory, self::createMutex($context->getTask()->getId(), $context->getScheduleRunContext()->getStartTime()))) {
            throw new SkipTask('Task running on another server.');
        }
    }

    public function supports(object $extension): bool
    {
        return $extension instanceof SingleServerExtension;
    }

    private static function createMutex(string $id, \DateTimeInterface $timestamp): string
    {
        return $id.$timestamp->format('Hi');
    }
}
