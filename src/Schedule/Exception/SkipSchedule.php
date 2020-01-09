<?php

namespace Zenstruck\ScheduleBundle\Schedule\Exception;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SkipSchedule extends \DomainException
{
    public function __construct(string $reason)
    {
        parent::__construct($reason);
    }
}
