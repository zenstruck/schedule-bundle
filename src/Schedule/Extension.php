<?php

namespace Zenstruck\ScheduleBundle\Schedule;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
interface Extension
{
    public const SCHEDULE_FILTER = 'Filter Schedule';
    public const SCHEDULE_BEFORE = 'Before Schedule';
    public const SCHEDULE_AFTER = 'After Schedule';
    public const SCHEDULE_SUCCESS = 'On Schedule Success';
    public const SCHEDULE_FAILURE = 'On Schedule Failure';
    public const TASK_FILTER = 'Filter Task';
    public const TASK_BEFORE = 'Before Task';
    public const TASK_AFTER = 'After Task';
    public const TASK_SUCCESS = 'On Task Success';
    public const TASK_FAILURE = 'On Task Failure';

    public function __toString(): string;
}
