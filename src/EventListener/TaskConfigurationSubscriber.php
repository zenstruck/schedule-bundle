<?php

namespace Zenstruck\ScheduleBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\BuildScheduleEvent;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;
use Zenstruck\ScheduleBundle\Schedule\Task\CompoundTask;
use Zenstruck\ScheduleBundle\Schedule\Task\NullTask;
use Zenstruck\ScheduleBundle\Schedule\Task\ProcessTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TaskConfigurationSubscriber implements EventSubscriberInterface
{
    private const PROCESS_TASK_PREFIX = 'bash:';

    private $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public static function getSubscribedEvents(): array
    {
        return [BuildScheduleEvent::class => 'configureTasks'];
    }

    public function configureTasks(BuildScheduleEvent $event): void
    {
        foreach ($this->config as $taskConfig) {
            $this->addTask($event->getSchedule(), $taskConfig);
        }
    }

    private function addTask(Schedule $schedule, array $config): void
    {
        $task = $this->createTask($config['command']);

        $task->cron($config['frequency']);

        if ($config['description']) {
            $task->description($config['description']);
        }

        if ($config['timezone']) {
            $task->timezone($config['timezone']);
        }

        if ($config['without_overlapping']['enabled']) {
            $task->withoutOverlapping($config['without_overlapping']['ttl']);
        }

        if ($config['only_between']['enabled']) {
            $task->onlyBetween($config['only_between']['start'], $config['only_between']['end']);
        }

        if ($config['unless_between']['enabled']) {
            $task->unlessBetween($config['unless_between']['start'], $config['unless_between']['end']);
        }

        if ($config['ping_before']['enabled']) {
            $task->pingBefore($config['ping_before']['url'], $config['ping_before']['method'], $config['ping_before']['options']);
        }

        if ($config['ping_after']['enabled']) {
            $task->pingAfter($config['ping_after']['url'], $config['ping_after']['method'], $config['ping_after']['options']);
        }

        if ($config['ping_on_success']['enabled']) {
            $task->pingOnSuccess($config['ping_on_success']['url'], $config['ping_on_success']['method'], $config['ping_on_success']['options']);
        }

        if ($config['ping_on_failure']['enabled']) {
            $task->pingOnFailure($config['ping_on_failure']['url'], $config['ping_on_failure']['method'], $config['ping_on_failure']['options']);
        }

        if ($config['email_after']['enabled']) {
            $task->emailAfter($config['email_after']['to'], $config['email_after']['subject']);
        }

        if ($config['email_on_failure']['enabled']) {
            $task->emailOnFailure($config['email_on_failure']['to'], $config['email_on_failure']['subject']);
        }

        $schedule->add($task);
    }

    private function createTask(array $commands): Task
    {
        if (1 === \count($commands)) {
            return $this->createSingleTask(\array_values($commands)[0]);
        }

        $task = new CompoundTask();

        foreach ($commands as $description => $command) {
            $subTask = $this->createSingleTask($command);

            if (!\is_numeric($description)) {
                $subTask->description($description);
            }

            $task->add($subTask);
        }

        return $task;
    }

    private function createSingleTask(?string $command): Task
    {
        if (null === $command) {
            return new NullTask('to be overridden');
        }

        if (0 !== \mb_strpos($command, self::PROCESS_TASK_PREFIX)) {
            return new CommandTask($command);
        }

        return new ProcessTask(\trim(\mb_substr($command, \mb_strlen(self::PROCESS_TASK_PREFIX))));
    }
}
