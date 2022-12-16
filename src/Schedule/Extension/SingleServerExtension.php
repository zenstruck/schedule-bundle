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
final class SingleServerExtension extends LockingExtension implements HasMissingDependencyMessage
{
    public const DEFAULT_TTL = 3600;

    /**
     * @param int $ttl Maximum expected lock duration in seconds
     */
    public function __construct(int $ttl = self::DEFAULT_TTL)
    {
        parent::__construct($ttl);
    }

    public function __toString(): string
    {
        return 'Run on single server';
    }

    public static function getMissingDependencyMessage(): string
    {
        return 'To use "onSingleServer" you must configure a lock factory (config path: "zenstruck_schedule.single_server_lock_factory").';
    }
}
