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
abstract class SelfHandlingExtension implements Extension
{
    /**
     * Skip entire schedule if \Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule
     * exception is thrown.
     *
     * @throws SkipSchedule
     */
    public function filterSchedule(BeforeScheduleEvent $event): void
    {
        // noop
    }

    /**
     * Executes before the schedule runs.
     */
    public function beforeSchedule(BeforeScheduleEvent $event): void
    {
        // noop
    }

    /**
     * Executes after the schedule runs.
     */
    public function afterSchedule(AfterScheduleEvent $event): void
    {
        // noop
    }

    /**
     * Executes if the schedule ran with no failures.
     */
    public function onScheduleSuccess(AfterScheduleEvent $event): void
    {
        // noop
    }

    /**
     * Executes if the schedule ran with failures.
     */
    public function onScheduleFailure(AfterScheduleEvent $event): void
    {
        // noop
    }

    /**
     * Skip task if \Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask exception
     * is thrown.
     *
     * @throws SkipTask
     */
    public function filterTask(BeforeTaskEvent $event): void
    {
        // noop
    }

    /**
     * Executes before the task runs (not if skipped).
     */
    public function beforeTask(BeforeTaskEvent $event): void
    {
        // noop
    }

    /**
     * Executes after the task runs (not if skipped).
     */
    public function afterTask(AfterTaskEvent $event): void
    {
        // noop
    }

    /**
     * Executes if the task ran successfully (not if skipped).
     */
    public function onTaskSuccess(AfterTaskEvent $event): void
    {
        // noop
    }

    /**
     * Executes if the task failed (not if skipped).
     */
    public function onTaskFailure(AfterTaskEvent $event): void
    {
        // noop
    }
}
