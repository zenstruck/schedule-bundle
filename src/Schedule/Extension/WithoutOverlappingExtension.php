<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Symfony\Component\Lock\LockFactory;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class WithoutOverlappingExtension implements Extension, HasMissingHandlerMessage
{
    public const DEFAULT_TTL = 86400;

    private $ttl;
    private $lock;

    /**
     * @param int $ttl Maximum expected lock duration in seconds
     */
    public function __construct(int $ttl = self::DEFAULT_TTL)
    {
        $this->ttl = $ttl;
        $this->lock = new Lock();
    }

    public function __toString(): string
    {
        return 'Without overlapping';
    }

    public function aquireLock(LockFactory $lockFactory, string $mutex): void
    {
        if (!$this->lock->aquire($lockFactory, $mutex, $this->ttl)) {
            throw new SkipTask('Task running in another process.');
        }
    }

    public function releaseLock(): void
    {
        $this->lock->release();
    }

    public function getMissingHandlerMessage(): string
    {
        return 'Symfony Lock is required to use the without overlapping extension. Install with "composer require symfony/lock".';
    }
}
