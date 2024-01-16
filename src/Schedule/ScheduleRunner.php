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

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zenstruck\ScheduleBundle\Event\AfterScheduleEvent;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Event\BuildScheduleEvent;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\MissingDependency;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandlerRegistry;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleRunner
{
    /** @var iterable<TaskRunner> */
    private $taskRunners;

    /** @var ExtensionHandlerRegistry */
    private $extensions;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    /**
     * @param iterable<TaskRunner> $taskRunners
     */
    public function __construct(iterable $taskRunners, ExtensionHandlerRegistry $handlerRegistry, EventDispatcherInterface $dispatcher)
    {
        $this->taskRunners = $taskRunners;
        $this->extensions = $handlerRegistry;
        $this->dispatcher = $dispatcher;
    }

    /**
     * @param string ...$taskIds Task ID's to force run
     */
    public function __invoke(string ...$taskIds): Schedule\ScheduleRunContext
    {
        $scheduleRunContext = $this->createRunContext($taskIds);

        try {
            $this->dispatcher->dispatch(new BeforeScheduleEvent($scheduleRunContext));
            $this->extensions->beforeSchedule($scheduleRunContext);
        } catch (SkipSchedule $e) {
            $scheduleRunContext->skip($e);

            $this->dispatcher->dispatch(new AfterScheduleEvent($scheduleRunContext));

            return $scheduleRunContext;
        }

        $taskRunContexts = [];

        foreach ($scheduleRunContext->dueTasks() as $task) {
            $taskRunContext = new TaskRunContext($scheduleRunContext, $task);

            $this->dispatcher->dispatch(new BeforeTaskEvent($taskRunContext));

            $taskRunContext->setResult($this->runTask($taskRunContext));

            $this->postRun($taskRunContext);

            $this->dispatcher->dispatch(new AfterTaskEvent($taskRunContext));

            $taskRunContexts[] = $taskRunContext;
        }

        $scheduleRunContext->setTaskRunContexts(...$taskRunContexts);

        $this->extensions->afterSchedule($scheduleRunContext);
        $this->dispatcher->dispatch(new AfterScheduleEvent($scheduleRunContext));

        return $scheduleRunContext;
    }

    public function buildSchedule(): Schedule
    {
        $this->dispatcher->dispatch(new BuildScheduleEvent($schedule = new Schedule()));

        return $schedule;
    }

    public function runnerFor(Schedule\Task $task): TaskRunner
    {
        foreach ($this->taskRunners as $runner) {
            if ($runner->supports($task)) {
                return $runner;
            }
        }

        throw MissingDependency::noTaskRunner($task);
    }

    private function runTask(TaskRunContext $context): Result
    {
        $task = $context->getTask();

        try {
            $this->extensions->beforeTask($context);

            return $this->runnerFor($task)($task);
        } catch (SkipTask $e) {
            return $e->createResult($task);
        } catch (\Throwable $e) {
            return Result::exception($task, $e);
        }
    }

    private function postRun(TaskRunContext $context): void
    {
        try {
            $this->extensions->afterTask($context);
        } catch (\Throwable $e) {
            $context->setResult(Result::exception($context->getTask(), $e));
        }
    }

    /**
     * @param string[] $taskIds
     */
    private function createRunContext(array $taskIds): Schedule\ScheduleRunContext
    {
        $schedule = $this->buildSchedule();

        $tasks = \array_map(fn(string $id) => $schedule->getTask($id), $taskIds);

        return new Schedule\ScheduleRunContext($schedule, ...$tasks);
    }
}
