<?php

namespace Zenstruck\ScheduleBundle\Event;

use Symfony\Component\Console\Helper\Helper;
use Zenstruck\ScheduleBundle\Schedule;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ResultEvent extends ScheduleEvent
{
    private $duration;
    private $memory;

    public function __construct(Schedule $schedule, int $duration, int $memory)
    {
        parent::__construct($schedule);

        $this->duration = $duration;
        $this->memory = $memory;
    }

    final public function getDuration(): int
    {
        return $this->duration;
    }

    final public function getFormattedDuration(): string
    {
        return Helper::formatTime($this->duration);
    }

    final public function getMemory(): int
    {
        return $this->memory;
    }

    final public function getFormattedMemory(): string
    {
        return Helper::formatMemory($this->memory);
    }
}
