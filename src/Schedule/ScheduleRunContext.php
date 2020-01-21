<?php

namespace Zenstruck\ScheduleBundle\Schedule;

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleRunContext extends RunContext
{
    private $schedule;
    private $dueTasks;

    private $results;
    private $skipReason;

    private $successful;
    private $failures;
    private $skipped;
    private $run;

    public function __construct(Schedule $schedule, Task ...$dueTasks)
    {
        $this->schedule = $schedule;
        $this->dueTasks = $dueTasks;

        parent::__construct();
    }

    public function __toString(): string
    {
        return 'The Schedule';
    }

    public function schedule(): Schedule
    {
        return $this->schedule;
    }

    /**
     * @return Task[]
     */
    public function dueTasks(): array
    {
        return $this->dueTasks;
    }

    public function setResults(Result ...$results): void
    {
        $resultCount = \count($results);
        $dueCount = \count($this->dueTasks());

        if ($resultCount !== $dueCount) {
            throw new \LogicException(\sprintf('The number of results (%d) does not match the number of due tasks (%d).', $resultCount, $dueCount));
        }

        $this->markAsRun(\memory_get_peak_usage(true));

        $this->results = $results;
    }

    public function skip(SkipSchedule $exception): void
    {
        $this->skipReason = $exception->getMessage();
    }

    public function skipReason(): ?string
    {
        return $this->skipReason;
    }

    /**
     * @return Result[]
     */
    public function getResults(): array
    {
        $this->ensureHasRun();

        return $this->results;
    }

    public function isSuccessful(): bool
    {
        return $this->isSkipped() || 0 === \count($this->getFailures());
    }

    public function isFailure(): bool
    {
        return !$this->isSuccessful();
    }

    public function isSkipped(): bool
    {
        return null !== $this->skipReason;
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

        foreach ($this->getResults() as $result) {
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

        foreach ($this->getResults() as $result) {
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

        foreach ($this->getResults() as $result) {
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

        foreach ($this->getResults() as $result) {
            if ($result->hasRun()) {
                $this->run[] = $result;
            }
        }

        return $this->run;
    }
}
