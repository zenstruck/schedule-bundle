<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Symfony\Component\Lock\LockFactory;
use Zenstruck\ScheduleBundle\Schedule\Extension;

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

    public function aquireLock(LockFactory $lockFactory, string $mutex, \DateTimeInterface $timestamp): bool
    {
        $mutex .= $timestamp->format('Hi');

        return $this->lock->aquire($lockFactory, $mutex, $this->ttl);
    }

    public function getMissingHandlerMessage(): string
    {
        return 'To use "onSingleServer" you must configure a lock factory (config path: "zenstruck_schedule.single_server_handler").';
    }
}
