<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\CallbackExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\RunContext;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CallbackHandler extends ExtensionHandler
{
    /**
     * @param CallbackExtension $extension
     */
    public function filterSchedule(ScheduleRunContext $context, object $extension): void
    {
        $this->runIf($extension, Schedule::FILTER, $context);
    }

    /**
     * @param CallbackExtension $extension
     */
    public function beforeSchedule(ScheduleRunContext $context, object $extension): void
    {
        $this->runIf($extension, Schedule::BEFORE, $context);
    }

    /**
     * @param CallbackExtension $extension
     */
    public function afterSchedule(ScheduleRunContext $context, object $extension): void
    {
        $this->runIf($extension, Schedule::AFTER, $context);
    }

    /**
     * @param CallbackExtension $extension
     */
    public function onScheduleSuccess(ScheduleRunContext $context, object $extension): void
    {
        $this->runIf($extension, Schedule::SUCCESS, $context);
    }

    /**
     * @param CallbackExtension $extension
     */
    public function onScheduleFailure(ScheduleRunContext $context, object $extension): void
    {
        $this->runIf($extension, Schedule::FAILURE, $context);
    }

    /**
     * @param CallbackExtension $extension
     */
    public function filterTask(TaskRunContext $context, object $extension): void
    {
        $this->runIf($extension, Task::FILTER, $context);
    }

    /**
     * @param CallbackExtension $extension
     */
    public function beforeTask(TaskRunContext $context, object $extension): void
    {
        $this->runIf($extension, Task::BEFORE, $context);
    }

    /**
     * @param CallbackExtension $extension
     */
    public function afterTask(TaskRunContext $context, object $extension): void
    {
        $this->runIf($extension, Task::AFTER, $context);
    }

    /**
     * @param CallbackExtension $extension
     */
    public function onTaskSuccess(TaskRunContext $context, object $extension): void
    {
        $this->runIf($extension, Task::SUCCESS, $context);
    }

    /**
     * @param CallbackExtension $extension
     */
    public function onTaskFailure(TaskRunContext $context, object $extension): void
    {
        $this->runIf($extension, Task::FAILURE, $context);
    }

    public function supports(object $extension): bool
    {
        return $extension instanceof CallbackExtension;
    }

    private function runIf(CallbackExtension $extension, string $expectedHook, RunContext $context): void
    {
        if ($expectedHook === $extension->getHook()) {
            $extension->getCallback()($context);
        }
    }
}
