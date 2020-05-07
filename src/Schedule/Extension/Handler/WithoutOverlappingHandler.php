<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\PersistingStoreInterface;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Lock\Store\SemaphoreStore;
use Zenstruck\ScheduleBundle\Schedule\Exception\MissingDependency;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\WithoutOverlappingExtension;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class WithoutOverlappingHandler extends ExtensionHandler
{
    private $lockFactory;

    public function __construct(LockFactory $lockFactory = null)
    {
        if (null === $lockFactory && !\class_exists(LockFactory::class)) {
            throw new MissingDependency(WithoutOverlappingExtension::getMissingDependencyMessage());
        }

        $this->lockFactory = $lockFactory ?: new LockFactory(self::createLocalStore());
    }

    /**
     * @param WithoutOverlappingExtension $extension
     */
    public function filterTask(TaskRunContext $context, object $extension): void
    {
        if (!$extension->acquireLock($this->lockFactory, $context->getTask()->getId())) {
            throw new SkipTask('Task running in another process.');
        }
    }

    /**
     * @param WithoutOverlappingExtension $extension
     */
    public function afterTask(TaskRunContext $context, object $extension): void
    {
        $extension->releaseLock();
    }

    public function supports(object $extension): bool
    {
        return $extension instanceof WithoutOverlappingExtension;
    }

    private static function createLocalStore(): PersistingStoreInterface
    {
        if (SemaphoreStore::isSupported()) {
            return new SemaphoreStore();
        }

        return new FlockStore();
    }
}
