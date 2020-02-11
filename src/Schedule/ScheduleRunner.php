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
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;
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

    /**
     * @param string ...$taskIds Task ID's to force run
     */
    public function __invoke(string ...$taskIds): ScheduleRunContext
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

    public function runnerFor(Task $task): TaskRunner
    {
        foreach ($this->taskRunners as $runner) {
            if ($runner->supports($task)) {
                return $runner;
            }
        }

        throw new \LogicException(\sprintf('No task runner registered to handle "%s".', \get_class($task)));
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

    private function createRunContext(array $taskIds): ScheduleRunContext
    {
        $schedule = $this->buildSchedule();

        $tasks = \array_map(function (string $id) use ($schedule) {
            return $schedule->getTask($id);
        }, $taskIds);

        return new ScheduleRunContext($schedule, ...$tasks);
    }
}
