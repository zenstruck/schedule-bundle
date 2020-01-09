<?php

namespace Zenstruck\ScheduleBundle\Event;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleBuildEvent extends ScheduleEvent
{
    public const REGISTER = 0;
    public const POST_REGISTER = -100;
}
