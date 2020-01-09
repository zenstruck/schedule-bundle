<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Zenstruck\ScheduleBundle\Event\AfterScheduleEvent;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\SelfHandlingExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SelfHandlingHandler extends ExtensionHandler
{
    /**
     * @param SelfHandlingExtension $extension
     */
    public function filterSchedule(BeforeScheduleEvent $event, Extension $extension): void
    {
        $extension->filterSchedule($event);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function beforeSchedule(BeforeScheduleEvent $event, Extension $extension): void
    {
        $extension->beforeSchedule($event);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function afterSchedule(AfterScheduleEvent $event, Extension $extension): void
    {
        $extension->afterSchedule($event);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function onScheduleSuccess(AfterScheduleEvent $event, Extension $extension): void
    {
        $extension->onScheduleSuccess($event);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function onScheduleFailure(AfterScheduleEvent $event, Extension $extension): void
    {
        $extension->onScheduleFailure($event);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function filterTask(BeforeTaskEvent $event, Extension $extension): void
    {
        $extension->filterTask($event);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function beforeTask(BeforeTaskEvent $event, Extension $extension): void
    {
        $extension->beforeTask($event);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function afterTask(AfterTaskEvent $event, Extension $extension): void
    {
        $extension->afterTask($event);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function onTaskSuccess(AfterTaskEvent $event, Extension $extension): void
    {
        $extension->onTaskSuccess($event);
    }

    /**
     * @param SelfHandlingExtension $extension
     */
    public function onTaskFailure(AfterTaskEvent $event, Extension $extension): void
    {
        $extension->onTaskFailure($event);
    }

    public function supports(Extension $extension): bool
    {
        return $extension instanceof SelfHandlingExtension;
    }
}
