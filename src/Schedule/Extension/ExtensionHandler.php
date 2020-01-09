<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Event\AfterScheduleEvent;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ExtensionHandler
{
    /**
     * Skip entire schedule if \Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule
     * exception is thrown.
     *
     * @throws SkipSchedule
     */
    public function filterSchedule(BeforeScheduleEvent $event, Extension $extension): void
    {
        // noop
    }

    /**
     * Executes before the schedule runs.
     */
    public function beforeSchedule(BeforeScheduleEvent $event, Extension $extension): void
    {
        // noop
    }

    /**
     * Executes after the schedule runs.
     */
    public function afterSchedule(AfterScheduleEvent $event, Extension $extension): void
    {
        // noop
    }

    /**
     * Executes if the schedule ran with no failures.
     */
    public function onScheduleSuccess(AfterScheduleEvent $event, Extension $extension): void
    {
        // noop
    }

    /**
     * Executes if the schedule ran with failures.
     */
    public function onScheduleFailure(AfterScheduleEvent $event, Extension $extension): void
    {
        // noop
    }

    /**
     * Skip task if \Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask exception
     * is thrown.
     *
     * @throws SkipTask
     */
    public function filterTask(BeforeTaskEvent $event, Extension $extension): void
    {
        // noop
    }

    /**
     * Executes before the task runs (not if skipped).
     */
    public function beforeTask(BeforeTaskEvent $event, Extension $extension): void
    {
        // noop
    }

    /**
     * Executes after the task runs (not if skipped).
     */
    public function afterTask(AfterTaskEvent $event, Extension $extension): void
    {
        // noop
    }

    /**
     * Executes if the task ran successfully (not if skipped).
     */
    public function onTaskSuccess(AfterTaskEvent $event, Extension $extension): void
    {
        // noop
    }

    /**
     * Executes if the task failed (not if skipped).
     */
    public function onTaskFailure(AfterTaskEvent $event, Extension $extension): void
    {
        // noop
    }

    abstract public function supports(Extension $extension): bool;
}
