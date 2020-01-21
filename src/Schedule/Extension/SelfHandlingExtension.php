<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

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
    public function filterSchedule(ScheduleRunContext $context): void
    {
        // noop
    }

    /**
     * Executes before the schedule runs.
     */
    public function beforeSchedule(ScheduleRunContext $context): void
    {
        // noop
    }

    /**
     * Executes after the schedule runs.
     */
    public function afterSchedule(ScheduleRunContext $context): void
    {
        // noop
    }

    /**
     * Executes if the schedule ran with no failures.
     */
    public function onScheduleSuccess(ScheduleRunContext $context): void
    {
        // noop
    }

    /**
     * Executes if the schedule ran with failures.
     */
    public function onScheduleFailure(ScheduleRunContext $context): void
    {
        // noop
    }

    /**
     * Skip task if \Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask exception
     * is thrown.
     *
     * @throws SkipTask
     */
    public function filterTask(TaskRunContext $context): void
    {
        // noop
    }

    /**
     * Executes before the task runs (not if skipped).
     */
    public function beforeTask(TaskRunContext $context): void
    {
        // noop
    }

    /**
     * Executes after the task runs (not if skipped).
     */
    public function afterTask(TaskRunContext $context): void
    {
        // noop
    }

    /**
     * Executes if the task ran successfully (not if skipped).
     */
    public function onTaskSuccess(TaskRunContext $context): void
    {
        // noop
    }

    /**
     * Executes if the task failed (not if skipped).
     */
    public function onTaskFailure(TaskRunContext $context): void
    {
        // noop
    }
}
