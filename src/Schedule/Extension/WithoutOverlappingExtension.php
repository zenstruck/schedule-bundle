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

use Zenstruck\ScheduleBundle\Schedule\HasMissingDependencyMessage;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class WithoutOverlappingExtension extends LockingExtension implements HasMissingDependencyMessage
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

    public static function getMissingDependencyMessage(): string
    {
        return 'Symfony Lock is required to use the without overlapping extension. Install with "composer require symfony/lock".';
    }
}
