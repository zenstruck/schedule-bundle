<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
