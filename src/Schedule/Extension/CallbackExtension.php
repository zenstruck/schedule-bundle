<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\RunContext;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CallbackExtension extends SelfHandlingExtension
{
    private $hook;
    private $callback;

    private function __construct(string $hook, callable $callback)
    {
        $this->hook = $hook;
        $this->callback = $callback;
    }

    public function __toString(): string
    {
        return \sprintf('%s callback: %s', $this->hook, self::createDescriptionFromCallback($this->callback));
    }

    public function filterSchedule(ScheduleRunContext $context): void
    {
        $this->runIf(Extension::SCHEDULE_FILTER, $context);
    }

    public function beforeSchedule(ScheduleRunContext $context): void
    {
        $this->runIf(Extension::SCHEDULE_BEFORE, $context);
    }

    public function afterSchedule(ScheduleRunContext $context): void
    {
        $this->runIf(Extension::SCHEDULE_AFTER, $context);
    }

    public function onScheduleSuccess(ScheduleRunContext $context): void
    {
        $this->runIf(Extension::SCHEDULE_SUCCESS, $context);
    }

    public function onScheduleFailure(ScheduleRunContext $context): void
    {
        $this->runIf(Extension::SCHEDULE_FAILURE, $context);
    }

    public function filterTask(TaskRunContext $context): void
    {
        $this->runIf(Extension::TASK_FILTER, $context);
    }

    public function beforeTask(TaskRunContext $context): void
    {
        $this->runIf(Extension::TASK_BEFORE, $context);
    }

    public function afterTask(TaskRunContext $context): void
    {
        $this->runIf(Extension::TASK_AFTER, $context);
    }

    public function onTaskSuccess(TaskRunContext $context): void
    {
        $this->runIf(Extension::TASK_SUCCESS, $context);
    }

    public function onTaskFailure(TaskRunContext $context): void
    {
        $this->runIf(Extension::TASK_FAILURE, $context);
    }

    public static function taskFilter(callable $callback): self
    {
        return new self(Extension::TASK_FILTER, $callback);
    }

    public static function taskBefore(callable $callback): self
    {
        return new self(Extension::TASK_BEFORE, $callback);
    }

    public static function taskAfter(callable $callback): self
    {
        return new self(Extension::TASK_AFTER, $callback);
    }

    public static function taskSuccess(callable $callback): self
    {
        return new self(Extension::TASK_SUCCESS, $callback);
    }

    public static function taskFailure(callable $callback): self
    {
        return new self(Extension::TASK_FAILURE, $callback);
    }

    public static function scheduleFilter(callable $callback): self
    {
        return new self(Extension::SCHEDULE_FILTER, $callback);
    }

    public static function scheduleBefore(callable $callback): self
    {
        return new self(Extension::SCHEDULE_BEFORE, $callback);
    }

    public static function scheduleAfter(callable $callback): self
    {
        return new self(Extension::SCHEDULE_AFTER, $callback);
    }

    public static function scheduleSuccess(callable $callback): self
    {
        return new self(Extension::SCHEDULE_SUCCESS, $callback);
    }

    public static function scheduleFailure(callable $callback): self
    {
        return new self(Extension::SCHEDULE_FAILURE, $callback);
    }

    public static function createDescriptionFromCallback(callable $callback): string
    {
        $ref = new \ReflectionFunction(\Closure::fromCallable($callback));

        if ($class = $ref->getClosureScopeClass()) {
            return "{$class->getName()}:{$ref->getStartLine()}";
        }

        return $ref->getName();
    }

    private function runIf(string $expectedHook, RunContext $context): void
    {
        if ($expectedHook === $this->hook) {
            ($this->callback)($context);
        }
    }
}
