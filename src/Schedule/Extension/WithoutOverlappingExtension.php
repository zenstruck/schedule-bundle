<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class WithoutOverlappingExtension extends SelfHandlingExtension
{
    public const DEFAULT_TTL = 86400;

    private $ttl;
    private $lock;
    private $lockFactory;

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
        return 'Without overlapping';
    }

    public function filterTask(TaskRunContext $context): void
    {
        if (!$this->lock->aquire($this->getLockFactory(), $context->task()->getId(), $this->ttl)) {
            throw new SkipTask('Task running in another process.');
        }
    }

    public function afterTask(TaskRunContext $context): void
    {
        $this->lock->release();
    }

    public function setLockFactory(LockFactory $lockFactory): self
    {
        $this->lockFactory = $lockFactory;

        return $this;
    }

    private function getLockFactory(): LockFactory
    {
        return $this->lockFactory ?: $this->lockFactory = new LockFactory(self::createLocalStore());
    }

    private static function createLocalStore(): PersistingStoreInterface
    {
        if (SemaphoreStore::isSupported()) {
            return new SemaphoreStore();
        }

        return new FlockStore();
    }
}
