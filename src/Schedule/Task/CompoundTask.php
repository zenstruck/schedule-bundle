<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Process\Process;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @implements \IteratorAggregate<Task>
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
     * @param Task\string|null $description optional description
     */
    public function addCommand(string $name, array $arguments = [], ?string $description = null): self
    {
        return $this->addWithDescription(new Task\CommandTask($name, ...$arguments), $description);
    }

    /**
     * @see CallbackTask::__construct()
     *
     * @param Task\string|null $description optional description
     */
    public function addCallback(callable $callback, ?string $description = null): self
    {
        return $this->addWithDescription(new Task\CallbackTask($callback), $description);
    }

    /**
     * @see ProcessTask::__construct()
     *
     * @param string|Process   $process
     * @param Task\string|null $description optional description
     */
    public function addProcess($process, ?string $description = null): self
    {
        return $this->addWithDescription(new Task\ProcessTask($process), $description);
    }

    /**
     * @see PingTask::__construct()
     *
     * @param Task\string|null $description optional description
     */
    public function addPing(string $url, string $method = 'GET', array $options = [], ?string $description = null): self
    {
        return $this->addWithDescription(new Task\PingTask($url, $method, $options), $description);
    }

    /**
     * @see MessageTask::__construct()
     *
     * @param object|Envelope  $message
     * @param Task\string|null $description optional description
     */
    public function addMessage(object $message, array $stamps = [], ?string $description = null): self
    {
        return $this->addWithDescription(new Task\MessageTask($message, $stamps), $description);
    }

    /**
     * @return \Traversable<Task>
     */
    public function getIterator(): \Traversable
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

    private function addWithDescription(Task $task, ?string $description = null): self
    {
        if ($description) {
            $task->description($description);
        }

        return $this->add($task);
    }
}
