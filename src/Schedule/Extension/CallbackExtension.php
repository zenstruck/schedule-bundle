<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CallbackExtension
{
    /** @var string */
    private $hook;

    /** @var callable */
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
        return new self(Task::FILTER, $callback);
    }

    public static function taskBefore(callable $callback): self
    {
        return new self(Task::BEFORE, $callback);
    }

    public static function taskAfter(callable $callback): self
    {
        return new self(Task::AFTER, $callback);
    }

    public static function taskSuccess(callable $callback): self
    {
        return new self(Task::SUCCESS, $callback);
    }

    public static function taskFailure(callable $callback): self
    {
        return new self(Task::FAILURE, $callback);
    }

    public static function scheduleFilter(callable $callback): self
    {
        return new self(Schedule::FILTER, $callback);
    }

    public static function scheduleBefore(callable $callback): self
    {
        return new self(Schedule::BEFORE, $callback);
    }

    public static function scheduleAfter(callable $callback): self
    {
        return new self(Schedule::AFTER, $callback);
    }

    public static function scheduleSuccess(callable $callback): self
    {
        return new self(Schedule::SUCCESS, $callback);
    }

    public static function scheduleFailure(callable $callback): self
    {
        return new self(Schedule::FAILURE, $callback);
    }

    public static function createDescriptionFromCallback(callable $callback): string
    {
        if (\is_array($callback)) {
            return \sprintf('%s::%s()', \is_object($callback[0]) ? \get_class($callback[0]) : $callback[0], $callback[1]);
        }

        if (\is_object($callback) && !$callback instanceof \Closure && \method_exists($callback, '__invoke')) {
            return \sprintf('%s::__invoke()', \get_class($callback));
        }

        $ref = new \ReflectionFunction(\Closure::fromCallable($callback));

        if ($class = $ref->getClosureScopeClass()) {
            return "{$class->getName()}:{$ref->getStartLine()}";
        }

        return $ref->getName().'()';
    }
}
