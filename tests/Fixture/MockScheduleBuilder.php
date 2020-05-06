<?php

namespace Zenstruck\ScheduleBundle\Tests\Fixture;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Zenstruck\ScheduleBundle\EventListener\ScheduleBuilderSubscriber;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandlerRegistry;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunner;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MockScheduleBuilder implements ScheduleBuilder
{
    private $tasks = [];
    private $extensions = [];
    private $runners = [];
    private $subscribers = [];
    private $handlers = [];
    private $builders = [];

    public function addTask(Task $task): self
    {
        $this->tasks[] = $task;

        return $this;
    }

    public function addExtension(Extension $extension): self
    {
        $this->extensions[] = $extension;

        return $this;
    }

    public function addRunner(TaskRunner $runner): self
    {
        $this->runners[] = $runner;

        return $this;
    }

    public function addSubscriber(EventSubscriberInterface $subscriber): self
    {
        $this->subscribers[] = $subscriber;

        return $this;
    }

    public function addHandler(ExtensionHandler $handler): self
    {
        $this->handlers[] = $handler;

        return $this;
    }

    public function addBuilder(ScheduleBuilder $builder): self
    {
        $this->builders[] = $builder;

        return $this;
    }

    public function run(string ...$taskIds): ScheduleRunContext
    {
        return $this->getRunner()(...$taskIds);
    }

    public function getRunner(EventDispatcherInterface $dispatcher = null): ScheduleRunner
    {
        $dispatcher = $dispatcher ?: new EventDispatcher();
        $dispatcher->addSubscriber(new ScheduleBuilderSubscriber(\array_merge($this->builders, [$this])));

        foreach ($this->subscribers as $subscriber) {
            $dispatcher->addSubscriber($subscriber);
        }

        return new ScheduleRunner(\array_merge($this->runners, [new MockTaskRunner()]), new ExtensionHandlerRegistry($this->handlers), $dispatcher);
    }

    public function buildSchedule(Schedule $schedule): void
    {
        foreach ($this->tasks as $task) {
            $schedule->add($task);
        }

        foreach ($this->extensions as $extension) {
            $schedule->addExtension($extension);
        }
    }
}
