<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface HasMissingHandlerMessage
{
    public function getMissingHandlerMessage(): string;
}
