<?php

namespace Zenstruck\ScheduleBundle\Event;

use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class AfterScheduleEvent extends ResultEvent
{
    private $results;

    private $successful;
    private $failures;
    private $skipped;
    private $run;
    private $skippedReason;

    /**
     * @param Result[] $results
     */
    public function __construct(BeforeScheduleEvent $beforeScheduleEvent, array $results)
    {
        $scheduleRunContext = $beforeScheduleEvent->getScheduleRunContext();

        parent::__construct(
            $scheduleRunContext,
            \time() - $scheduleRunContext->startTime(),
            \memory_get_peak_usage(true)
        );

        $this->results = $results;
    }

    public static function skip(SkipSchedule $exception, BeforeScheduleEvent $beforeScheduleEvent): self
    {
        $event = new self($beforeScheduleEvent, []);
        $event->skippedReason = $exception->getMessage();

        return $event;
    }

    /**
     * @return Result[]
     */
    public function getResults(): array
    {
        return $this->results;
    }

    public function isSuccessful(): bool
    {
        return 0 === \count($this->getFailures());
    }

    public function isFailure(): bool
    {
        return !$this->isSuccessful();
    }

    public function isSkipped(): bool
    {
        return null !== $this->skippedReason;
    }

    public function getSkipReason(): ?string
    {
        return $this->skippedReason;
    }

    /**
     * @return Result[]
     */
    public function getSuccessful(): array
    {
        if (null !== $this->successful) {
            return $this->successful;
        }

        $this->successful = [];

        foreach ($this->results as $result) {
            if ($result->isSuccessful()) {
                $this->successful[] = $result;
            }
        }

        return $this->successful;
    }

    /**
     * @return Result[]
     */
    public function getFailures(): array
    {
        if (null !== $this->failures) {
            return $this->failures;
        }

        $this->failures = [];

        foreach ($this->results as $result) {
            if ($result->isFailure()) {
                $this->failures[] = $result;
            }
        }

        return $this->failures;
    }

    /**
     * @return Result[]
     */
    public function getSkipped(): array
    {
        if (null !== $this->skipped) {
            return $this->skipped;
        }

        $this->skipped = [];

        foreach ($this->results as $result) {
            if ($result->isSkipped()) {
                $this->skipped[] = $result;
            }
        }

        return $this->skipped;
    }

    /**
     * @return Result[]
     */
    public function getRun(): array
    {
        if (null !== $this->run) {
            return $this->run;
        }

        $this->run = [];

        foreach ($this->results as $result) {
            if ($result->hasRun()) {
                $this->run[] = $result;
            }
        }

        return $this->run;
    }
}
