<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Schedule\Extension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CallbackExtension implements Extension
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

    public function getHook(): string
    {
        return $this->hook;
    }

    public function getCallback(): callable
    {
        return $this->callback;
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
}
