<?php

namespace Zenstruck\ScheduleBundle\Event;

use Symfony\Component\Console\Helper\Helper;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class ResultEvent extends ScheduleEvent
{
    private $duration;
    private $memory;

    public function __construct(ScheduleRunContext $scheduleRunContext, int $duration, int $memory)
    {
        parent::__construct($scheduleRunContext);

        $this->duration = $duration;
        $this->memory = $memory;
    }

    final public function getDuration(): int
    {
        return $this->duration;
    }

    final public function getFormattedDuration(): string
    {
        return Helper::formatTime($this->getDuration());
    }

    final public function getMemory(): int
    {
        return $this->memory;
    }

    final public function getFormattedMemory(): string
    {
        return Helper::formatMemory($this->getMemory());
    }
}
