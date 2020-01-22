<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Symfony\Component\Lock\LockFactory;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\WithoutOverlappingExtension;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

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
    public function filterTask(TaskRunContext $context, Extension $extension): void
    {
        $extension->setLockFactory($this->lockFactory)->filterTask($context);
    }

    /**
     * @param WithoutOverlappingExtension $extension
     */
    public function afterTask(TaskRunContext $context, Extension $extension): void
    {
        $extension->setLockFactory($this->lockFactory)->afterTask($context);
    }

    public function supports(Extension $extension): bool
    {
        return $extension instanceof WithoutOverlappingExtension;
    }
}
