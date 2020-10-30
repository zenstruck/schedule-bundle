<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

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
     * @see CommandTask::__construct()
     *
     * @param string|null $description optional description
     */
    public function addCommand(string $name, array $arguments = [], string $description = null): self
    {
        return $this->addWithDescription(new CommandTask($name, ...$arguments), $description);
    }

    /**
     * @see CallbackTask::__construct()
     *
     * @param string|null $description optional description
     */
    public function addCallback(callable $callback, string $description = null): self
    {
        return $this->addWithDescription(new CallbackTask($callback), $description);
    }

    /**
     * @see ProcessTask::__construct()
     *
     * @param string|null $description optional description
     */
    public function addProcess($process, string $description = null): self
    {
        return $this->addWithDescription(new ProcessTask($process), $description);
    }

    /**
     * @see PingTask::__construct()
     *
     * @param string|null $description optional description
     */
    public function addPing(string $url, string $method = 'GET', array $options = [], string $description = null): self
    {
        return $this->addWithDescription(new PingTask($url, $method, $options), $description);
    }

    /**
     * @return Task[]
     */
    public function getIterator(): iterable
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
