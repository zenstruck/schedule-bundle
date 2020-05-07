<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class LockingExtension
{
    private $ttl;

    /** @var LockInterface|null */
    private $lock;

    public function __construct(int $ttl)
    {
        $this->ttl = $ttl;
    }

    final public function acquireLock(LockFactory $lockFactory, string $mutex): bool
    {
        if (null !== $this->lock) {
            throw new \LogicException('A lock is already in place.');
        }

        $this->lock = $lockFactory->createLock('symfony-schedule-'.$mutex, $this->ttl);

        if ($this->lock->acquire()) {
            return true;
        }

        $this->lock = null;

        return false;
    }

    final public function releaseLock(): void
    {
        if ($this->lock) {
            $this->lock->release();

            $this->lock = null;
        }
    }
}
