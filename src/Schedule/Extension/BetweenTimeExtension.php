<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BetweenTimeExtension extends SelfHandlingExtension
{
    private $startTime;
    private $endTime;
    private $within;
    private $inclusive;

    private function __construct(string $startTime, string $endTime, bool $within, bool $inclusive)
    {
        $this->startTime = self::normalizeTime($startTime);
        $this->endTime = self::normalizeTime($endTime);
        $this->within = $within;
        $this->inclusive = $inclusive;
    }

    public function __toString(): string
    {
        if ($this->within) {
            return "Only run between {$this->startTime} and {$this->endTime}";
        }

        return "Only run if not between {$this->startTime} and {$this->endTime}";
    }

    public function filterTask(TaskRunContext $context): void
    {
        $isBetween = $this->isBetween($context->task()->getTimezone());

        if ($this->within && !$isBetween) {
            throw new SkipTask("Only runs between {$this->startTime} and {$this->endTime}");
        }

        if (!$this->within && $isBetween) {
            throw new SkipTask("Only runs if not between {$this->startTime} and {$this->endTime}");
        }
    }

    public static function whenWithin(string $startTime, string $endTime, bool $inclusive = true): self
    {
        return new self($startTime, $endTime, true, $inclusive);
    }

    public static function unlessWithin(string $startTime, string $endTime, bool $inclusive = true): self
    {
        return new self($startTime, $endTime, false, $inclusive);
    }

    private function isBetween(?\DateTimeZone $timezone): bool
    {
        [$now, $startTime, $endTime] = [
            new \DateTime(\date('Y-m-d H:i:00'), $timezone),
            self::parseTime($this->startTime, $timezone),
            self::parseTime($this->endTime, $timezone),
        ];

        if ($endTime < $startTime) {
            // account for overnight
            $endTime = $endTime->add(new \DateInterval('P1D'));
        }

        if ($this->inclusive) {
            return $now >= $startTime && $now <= $endTime;
        }

        return $now > $startTime && $now < $endTime;
    }

    private static function normalizeTime(string $time): string
    {
        return false === \mb_strpos($time, ':') ? "{$time}:00" : $time;
    }

    private static function parseTime(string $time, ?\DateTimeZone $timezone): \DateTime
    {
        [$hour, $minute] = \explode(':', $time, 2);

        return (new \DateTime('today', $timezone))
            ->add(new \DateInterval("PT{$hour}H{$minute}M"))
        ;
    }
}
