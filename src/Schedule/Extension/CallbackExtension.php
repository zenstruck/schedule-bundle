<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CallbackExtension
{
    /** @var callable */
    private $callback;

    private function __construct(
        private string $hook,
        callable $callback,
        private ?string $description = null)
    {
        $this->callback = $callback;
    }

    public function __toString(): string
    {
        return \sprintf('%s callback: %s', $this->hook, $this->description ?? self::createDescriptionFromCallback($this->callback));
    }

    public function getHook(): string
    {
        return $this->hook;
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public static function taskFilter(callable $callback, ?string $description = null): self
    {
        return new self(Task::FILTER, $callback, $description);
    }

    public static function taskBefore(callable $callback, ?string $description = null): self
    {
        return new self(Task::BEFORE, $callback, $description);
    }

    public static function taskAfter(callable $callback, ?string $description = null): self
    {
        return new self(Task::AFTER, $callback, $description);
    }

    public static function taskSuccess(callable $callback, ?string $description = null): self
    {
        return new self(Task::SUCCESS, $callback, $description);
    }

    public static function taskFailure(callable $callback, ?string $description = null): self
    {
        return new self(Task::FAILURE, $callback, $description);
    }

    public static function scheduleFilter(callable $callback, ?string $description = null): self
    {
        return new self(Schedule::FILTER, $callback, $description);
    }

    public static function scheduleBefore(callable $callback, ?string $description = null): self
    {
        return new self(Schedule::BEFORE, $callback, $description);
    }

    public static function scheduleAfter(callable $callback, ?string $description = null): self
    {
        return new self(Schedule::AFTER, $callback, $description);
    }

    public static function scheduleSuccess(callable $callback, ?string $description = null): self
    {
        return new self(Schedule::SUCCESS, $callback, $description);
    }

    public static function scheduleFailure(callable $callback, ?string $description = null): self
    {
        return new self(Schedule::FAILURE, $callback, $description);
    }

    public static function createDescriptionFromCallback(callable $callback): string
    {
        if (\is_array($callback)) {
            return \sprintf('%s::%s()', \is_object($callback[0]) ? \get_class($callback[0]) : $callback[0], $callback[1]);
        }

        if (\is_object($callback) && !$callback instanceof \Closure && \method_exists($callback, '__invoke')) {
            return \sprintf('%s::__invoke()', $callback::class);
        }

        $ref = new \ReflectionFunction(\Closure::fromCallable($callback));

        if ($class = $ref->getClosureScopeClass()) {
            return "{$class->getName()}:{$ref->getStartLine()}";
        }

        return $ref->getName().'()';
    }
}
