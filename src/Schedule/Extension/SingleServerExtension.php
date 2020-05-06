<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SingleServerExtension extends LockingExtension implements HasMissingHandlerMessage
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

    public function getMissingHandlerMessage(): string
    {
        return 'To use "onSingleServer" you must configure a lock factory (config path: "zenstruck_schedule.single_server_handler").';
    }
}
