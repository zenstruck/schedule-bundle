<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\LockInterface;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Lock
{
    /** @var LockInterface|null */
    private $lock;

    public function aquire(LockFactory $lockFactory, string $mutex, int $ttl): bool
    {
        if (null !== $this->lock) {
            throw new \LogicException('A lock is already in place.');
        }

        $this->lock = $lockFactory->createLock('symfony-schedule-'.$mutex, $ttl);

        if ($this->lock->acquire()) {
            return true;
        }

        $this->lock = null;

        return false;
    }

    public function release(): void
    {
        if ($this->lock) {
            $this->lock->release();

            $this->lock = null;
        }
    }
}
