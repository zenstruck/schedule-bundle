<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule;

use Symfony\Component\Console\Helper\Helper;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class RunContext
{
    private \DateTimeImmutable $startTime;
    private ?int $duration = null;
    private ?int $memory = null;

    public function __construct()
    {
        $this->startTime = new \DateTimeImmutable('now');
    }

    abstract public function __toString(): string;

    final public function getStartTime(): \DateTimeImmutable
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
        $this->duration = \time() - $this->startTime->getTimestamp();
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
