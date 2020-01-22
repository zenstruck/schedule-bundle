<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\SelfHandlingExtension;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SelfHandlingHandler extends ExtensionHandler
{
    /**
     * @param SelfHandlingExtension $extension
     */
    public function filterSchedule(ScheduleRunContext $context, Extension $extension): void
    {
        $extension->filterSchedule($context);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function beforeSchedule(ScheduleRunContext $context, Extension $extension): void
    {
        $extension->beforeSchedule($context);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function afterSchedule(ScheduleRunContext $context, Extension $extension): void
    {
        $extension->afterSchedule($context);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function onScheduleSuccess(ScheduleRunContext $context, Extension $extension): void
    {
        $extension->onScheduleSuccess($context);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function onScheduleFailure(ScheduleRunContext $context, Extension $extension): void
    {
        $extension->onScheduleFailure($context);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function filterTask(TaskRunContext $context, Extension $extension): void
    {
        $extension->filterTask($context);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function beforeTask(TaskRunContext $context, Extension $extension): void
    {
        $extension->beforeTask($context);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function afterTask(TaskRunContext $context, Extension $extension): void
    {
        $extension->afterTask($context);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function onTaskSuccess(TaskRunContext $context, Extension $extension): void
    {
        $extension->onTaskSuccess($context);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function onTaskFailure(TaskRunContext $context, Extension $extension): void
    {
        $extension->onTaskFailure($context);
    }

    public function supports(Extension $extension): bool
    {
        return $extension instanceof SelfHandlingExtension;
    }
}
