<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Builder;

use Symfony\Component\Console\Command\Command;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\CallbackTask;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ScheduledServiceBuilder implements ScheduleBuilder
{
    /** @var array<int,array{object,array<string,string>}> */
    private $services = [];

    /**
     * @param array<string,string> $attributes
     */
    public function add(object $service, array $attributes): void
    {
        $this->services[] = [$service, $attributes];
    }

    public function buildSchedule(Schedule $schedule): void
    {
        foreach ($this->services as [$service, $attributes]) {
            $task = $this->createTask($service, $attributes)
                ->cron($attributes['frequency'])
            ;

            if ($description = $attributes['description'] ?? null) {
                $task->description($description);
            }

            $schedule->add($task);
        }
    }

    /**
     * @param array<string,string> $attributes
     */
    public static function validate(?string $class, array $attributes): void
    {
        if (!\class_exists($class = (string) $class)) {
            throw new \LogicException('Class does not exist.');
        }

        if (!isset($attributes['frequency'])) {
            throw new \LogicException('Missing frequency tag attribute.');
        }

        if (\is_a($class, Command::class, true)) {
            return;
        }

        if (!isset($attributes['method'])) {
            throw new \LogicException('Missing method tag attribute.');
        }

        if ($attributes['arguments'] ?? null) {
            throw new \LogicException(\sprintf('%s::$arguments used on %s is not usable for non-%s services.', AsScheduledTask::class, $class, Command::class));
        }

        try {
            $method = new \ReflectionMethod($class, $attributes['method']);
        } catch (\ReflectionException $e) {
            throw new \LogicException(\sprintf('%s::%s() method is required to use with the %s attribute.', $class, $attributes['method'], AsScheduledTask::class));
        }

        if ($method->isStatic() || !$method->isPublic()) {
            throw new \LogicException(\sprintf('Method %s::%s() must non-static and public to use with the %s attribute.', $class, $attributes['method'], AsScheduledTask::class));
        }

        if ($method->getNumberOfRequiredParameters()) {
            throw new \LogicException(\sprintf('Method %s::%s() must not have any required parameters to use with the %s attribute.', $class, $attributes['method'], AsScheduledTask::class));
        }
    }

    /**
     * @param array<string,string> $attributes
     */
    private function createTask(object $service, array $attributes): Task
    {
        if ($service instanceof Command) {
            return new CommandTask((string) $service->getName(), (string) ($attributes['arguments'] ?? null));
        }

        $callback = [$service, $attributes['method']];

        \assert(\is_callable($callback));

        return new CallbackTask($callback);
    }
}
