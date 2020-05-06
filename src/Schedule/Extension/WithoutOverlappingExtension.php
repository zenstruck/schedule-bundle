<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Schedule\Extension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class WithoutOverlappingExtension extends LockingExtension implements Extension, HasMissingHandlerMessage
{
    public const DEFAULT_TTL = 86400;

    /**
     * @param int $ttl Maximum expected lock duration in seconds
     */
    public function __construct(int $ttl = self::DEFAULT_TTL)
    {
        parent::__construct($ttl);
    }

    public function __toString(): string
    {
        return 'Without overlapping';
    }

    public function getMissingHandlerMessage(): string
    {
        return 'Symfony Lock is required to use the without overlapping extension. Install with "composer require symfony/lock".';
    }
}
