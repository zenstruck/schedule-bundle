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

    public function __construct(Schedule $schedule, Task ...$forcedTasks)
    {
        $this->schedule = $schedule;
        $this->dueTasks = empty($forcedTasks) ? $schedule->due() : $forcedTasks;

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
     *
     * @throws \LogicException if has not yet run
     */
    public function getResults(): array
    {
        $this->ensureHasRun();

        return $this->results;
    }

    /**
     * @throws \LogicException if has not yet run and has not been marked as skipped
     */
    public function isSuccessful(): bool
    {
        return $this->isSkipped() || 0 === \count($this->getFailures());
    }

    /**
     * @throws \LogicException if has not yet run
     */
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
     *
     * @throws \LogicException if has not yet run
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
     *
     * @throws \LogicException if has not yet run
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
     *
     * @throws \LogicException if has not yet run
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
     *
     * @throws \LogicException if has not yet run
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
