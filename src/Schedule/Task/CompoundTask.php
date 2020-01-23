<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Symfony\Component\Process\Process;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CompoundTask extends Task implements \IteratorAggregate
{
    /** @var Task[] */
    private $tasks = [];

    public function __construct()
    {
        parent::__construct('compound task');
    }

    public function add(Task $task): self
    {
        if ($task instanceof self) {
            throw new \LogicException('Cannot nest compound tasks.');
        }

        $this->tasks[] = $task;

        return $this;
    }

    /**
     * @param string $name Command class or name (my:command)
     */
    public function addCommand(string $name, array $arguments = [], string $description = null): self
    {
        return $this->addWithDescription(new CommandTask($name, ...$arguments), $description);
    }

    /**
     * @param callable $callback Return value is considered "output"
     */
    public function addCallback(callable $callback, string $description = null): self
    {
        return $this->addWithDescription(new CallbackTask($callback), $description);
    }

    /**
     * @param string|Process $process
     */
    public function addProcess($process, string $description = null): self
    {
        return $this->addWithDescription(new ProcessTask($process), $description);
    }

    /**
     * @return Task[]
     */
    public function getIterator(): \Generator
    {
        foreach ($this->tasks as $task) {
            $task->cron($this->getExpression());

            if ($this->getTimezone()) {
                $task->timezone($this->getTimezone());
            }

            foreach ($this->getExtensions() as $extension) {
                $task->addExtension($extension);
            }

            yield $task;
        }
    }

    private function addWithDescription(Task $task, string $description = null): self
    {
        if ($description) {
            $task->description($description);
        }

        return $this->add($task);
    }
}
