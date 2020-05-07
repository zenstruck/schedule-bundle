<?php

namespace Zenstruck\ScheduleBundle\Schedule;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface HasMissingDependencyMessage
{
    public static function getMissingDependencyMessage(): string;
}
