<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension;
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
     * @param CallbackExtension|Extension $extension
     */
    public function filterSchedule(ScheduleRunContext $context, Extension $extension): void
    {
        $this->runIf($extension, Schedule::FILTER, $context);
    }

    /**
     * @param CallbackExtension|Extension $extension
     */
    public function beforeSchedule(ScheduleRunContext $context, Extension $extension): void
    {
        $this->runIf($extension, Schedule::BEFORE, $context);
    }

    /**
     * @param CallbackExtension|Extension $extension
     */
    public function afterSchedule(ScheduleRunContext $context, Extension $extension): void
    {
        $this->runIf($extension, Schedule::AFTER, $context);
    }

    /**
     * @param CallbackExtension|Extension $extension
     */
    public function onScheduleSuccess(ScheduleRunContext $context, Extension $extension): void
    {
        $this->runIf($extension, Schedule::SUCCESS, $context);
    }

    /**
     * @param CallbackExtension|Extension $extension
     */
    public function onScheduleFailure(ScheduleRunContext $context, Extension $extension): void
    {
        $this->runIf($extension, Schedule::FAILURE, $context);
    }

    /**
     * @param CallbackExtension|Extension $extension
     */
    public function filterTask(TaskRunContext $context, Extension $extension): void
    {
        $this->runIf($extension, Task::FILTER, $context);
    }

    /**
     * @param CallbackExtension|Extension $extension
     */
    public function beforeTask(TaskRunContext $context, Extension $extension): void
    {
        $this->runIf($extension, Task::BEFORE, $context);
    }

    /**
     * @param CallbackExtension|Extension $extension
     */
    public function afterTask(TaskRunContext $context, Extension $extension): void
    {
        $this->runIf($extension, Task::AFTER, $context);
    }

    /**
     * @param CallbackExtension|Extension $extension
     */
    public function onTaskSuccess(TaskRunContext $context, Extension $extension): void
    {
        $this->runIf($extension, Task::SUCCESS, $context);
    }

    /**
     * @param CallbackExtension|Extension $extension
     */
    public function onTaskFailure(TaskRunContext $context, Extension $extension): void
    {
        $this->runIf($extension, Task::FAILURE, $context);
    }

    public function supports(Extension $extension): bool
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
