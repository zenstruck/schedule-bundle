<?php

namespace Zenstruck\ScheduleBundle\Schedule;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zenstruck\ScheduleBundle\Event\AfterScheduleEvent;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Event\BuildScheduleEvent;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandlerRegistry;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleRunner
{
    private $taskRunners;
    private $extensions;
    private $dispatcher;

    public function __construct(iterable $taskRunners, ExtensionHandlerRegistry $handlerRegistry, EventDispatcherInterface $dispatcher)
    {
        $this->taskRunners = $taskRunners;
        $this->extensions = $handlerRegistry;
        $this->dispatcher = $dispatcher;
    }

    public function __invoke(): AfterScheduleEvent
    {
        $schedule = $this->buildSchedule();
        $beforeScheduleEvent = new BeforeScheduleEvent($schedule);

        try {
            $this->dispatcher->dispatch($beforeScheduleEvent);
            $this->extensions->beforeSchedule($beforeScheduleEvent);
        } catch (SkipSchedule $e) {
            $afterScheduleEvent = AfterScheduleEvent::skip($e, $beforeScheduleEvent);

            $this->dispatcher->dispatch($afterScheduleEvent);

            return $afterScheduleEvent;
        }

        $results = [];

        foreach ($schedule->due() as $task) {
            $beforeTaskEvent = new BeforeTaskEvent($beforeScheduleEvent, $task);

            $this->dispatcher->dispatch($beforeTaskEvent);

            $result = $this->runTask($beforeTaskEvent);
            $afterTaskEvent = $this->postRun($beforeTaskEvent, $result);

            $this->dispatcher->dispatch($afterTaskEvent);

            $results[] = $afterTaskEvent->getResult();
        }

        $afterScheduleEvent = new AfterScheduleEvent($beforeScheduleEvent, $results);

        $this->extensions->afterSchedule($afterScheduleEvent);
        $this->dispatcher->dispatch($afterScheduleEvent);

        return $afterScheduleEvent;
    }

    public function buildSchedule(): Schedule
    {
        $this->dispatcher->dispatch(new BuildScheduleEvent($schedule = new Schedule()));

        return $schedule;
    }

    public function runnerFor(Task $task): TaskRunner
    {
        foreach ($this->taskRunners as $runner) {
            if ($runner->supports($task)) {
                return $runner;
            }
        }

        throw new \LogicException(\sprintf('No task runner registered to handle "%s".', \get_class($task)));
    }

    private function runTask(BeforeTaskEvent $event): Result
    {
        $task = $event->getTask();

        try {
            $this->extensions->beforeTask($event);

            return $this->runnerFor($task)($task);
        } catch (SkipTask $e) {
            return $e->createResult($task);
        } catch (\Throwable $e) {
            return Result::exception($task, $e);
        }
    }

    private function postRun(BeforeTaskEvent $event, Result $result): AfterTaskEvent
    {
        $afterTaskEvent = new AfterTaskEvent($event, $result);

        try {
            $this->extensions->afterTask($afterTaskEvent);
        } catch (\Throwable $e) {
            return new AfterTaskEvent($event, Result::exception($event->getTask(), $e));
        }

        return $afterTaskEvent;
    }
}
