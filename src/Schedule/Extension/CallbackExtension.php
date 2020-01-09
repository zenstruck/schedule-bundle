<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Event\AfterScheduleEvent;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Event\ScheduleEvent;
use Zenstruck\ScheduleBundle\Schedule\Extension;

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

    public function filterSchedule(BeforeScheduleEvent $event): void
    {
        $this->runIf(Extension::SCHEDULE_FILTER, $event);
    }

    public function beforeSchedule(BeforeScheduleEvent $event): void
    {
        $this->runIf(Extension::SCHEDULE_BEFORE, $event);
    }

    public function afterSchedule(AfterScheduleEvent $event): void
    {
        $this->runIf(Extension::SCHEDULE_AFTER, $event);
    }

    public function onScheduleSuccess(AfterScheduleEvent $event): void
    {
        $this->runIf(Extension::SCHEDULE_SUCCESS, $event);
    }

    public function onScheduleFailure(AfterScheduleEvent $event): void
    {
        $this->runIf(Extension::SCHEDULE_FAILURE, $event);
    }

    public function filterTask(BeforeTaskEvent $event): void
    {
        $this->runIf(Extension::TASK_FILTER, $event);
    }

    public function beforeTask(BeforeTaskEvent $event): void
    {
        $this->runIf(Extension::TASK_BEFORE, $event);
    }

    public function afterTask(AfterTaskEvent $event): void
    {
        $this->runIf(Extension::TASK_AFTER, $event);
    }

    public function onTaskSuccess(AfterTaskEvent $event): void
    {
        $this->runIf(Extension::TASK_SUCCESS, $event);
    }

    public function onTaskFailure(AfterTaskEvent $event): void
    {
        $this->runIf(Extension::TASK_FAILURE, $event);
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

    private function runIf(string $expectedHook, ScheduleEvent $event): void
    {
        if ($expectedHook === $this->hook) {
            ($this->callback)($event);
        }
    }
}
