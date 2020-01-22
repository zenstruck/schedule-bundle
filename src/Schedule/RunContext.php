<?php

namespace Zenstruck\ScheduleBundle\Schedule;

use Symfony\Component\Console\Helper\Helper;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class RunContext
{
    private $startTime;
    private $duration;
    private $memory;

    public function __construct()
    {
        $this->startTime = \time();
    }

    abstract public function __toString(): string;

    final public function startTime(): int
    {
        return $this->startTime;
    }

    final public function hasRun(): bool
    {
        return null !== $this->memory;
    }

    final public function getDuration(): int
    {
        $this->ensureHasRun();

        return $this->duration;
    }

    final public function getFormattedDuration(): string
    {
        return Helper::formatTime($this->getDuration());
    }

    final public function getMemory(): int
    {
        $this->ensureHasRun();

        return $this->memory;
    }

    final public function getFormattedMemory(): string
    {
        return Helper::formatMemory($this->getMemory());
    }

    final protected function markAsRun(int $memory): void
    {
        $this->duration = \time() - $this->startTime();
        $this->memory = $memory;
    }

    /**
     * @throws \LogicException if has not yet run
     */
    final protected function ensureHasRun(): void
    {
        if (!$this->hasRun()) {
            throw new \LogicException("\"{$this}\" has not yet run.");
        }
    }
}
