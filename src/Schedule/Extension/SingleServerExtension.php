<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Symfony\Component\Lock\LockFactory;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SingleServerExtension implements Extension, HasMissingHandlerMessage
{
    public const DEFAULT_TTL = 3600;

    private $ttl;
    private $lock;

    /**
     * @param int $ttl Maximum expected lock duration in seconds
     */
    public function __construct(int $ttl = self::DEFAULT_TTL)
    {
        $this->ttl = $ttl;
        $this->lock = new Lock(self::class);
    }

    public function __toString(): string
    {
        return 'Run on single server';
    }

    public function aquireTaskLock(LockFactory $lockFactory, Task $task, int $timestamp): void
    {
        if (!$this->aquireLock($lockFactory, $task->getId(), $timestamp)) {
            throw new SkipTask('Task running on another server.');
        }
    }

    public function aquireScheduleLock(LockFactory $lockFactory, Schedule $schedule, int $timestamp): void
    {
        if (!$this->aquireLock($lockFactory, $schedule->getId(), $timestamp)) {
            throw new SkipSchedule('Schedule running on another server.');
        }
    }

    public function getMissingHandlerMessage(): string
    {
        return 'To use "onSingleServer" you must configure a lock factory (config path: "zenstruck_schedule.single_server_handler").';
    }

    private function aquireLock(LockFactory $lockFactory, string $mutex, int $timestamp): bool
    {
        $mutex .= \date('Hi', $timestamp);

        return $this->lock->aquire($lockFactory, $mutex, $this->ttl);
    }
}
