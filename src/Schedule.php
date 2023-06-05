<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle;

use Symfony\Component\Process\Process;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\CallbackExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\EmailExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\EnvironmentExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\NotifierExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\PingExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Schedule\HasExtensions;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\CallbackTask;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;
use Zenstruck\ScheduleBundle\Schedule\Task\CompoundTask;
use Zenstruck\ScheduleBundle\Schedule\Task\MessageTask;
use Zenstruck\ScheduleBundle\Schedule\Task\PingTask;
use Zenstruck\ScheduleBundle\Schedule\Task\ProcessTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Schedule
{
    use HasExtensions;

    public const FILTER = 'Filter Schedule';
    public const BEFORE = 'Before Schedule';
    public const AFTER = 'After Schedule';
    public const SUCCESS = 'On Schedule Success';
    public const FAILURE = 'On Schedule Failure';

    /** @var Task[] */
    private $tasks = [];

    /** @var Task[]|null */
    private $allTasks;

    /** @var Task[]|null */
    private $dueTasks;

    /** @var \DateTimeZone|null */
    private $timezone;

    public function getId(): string
    {
        $tasks = \array_map(
            fn(Task $task) => $task->getId(),
            $this->all()
        );

        return \sha1(\implode('', $tasks));
    }

    /**
     * @template T of Task
     *
     * @param T $task
     *
     * @return T
     */
    public function add(Task $task): Task
    {
        $this->resetCache();

        return $this->tasks[] = $task;
    }

    /**
     * @see CommandTask::__construct()
     */
    public function addCommand(string $name, string ...$arguments): CommandTask
    {
        return $this->add(new CommandTask($name, ...$arguments));
    }

    /**
     * @see CallbackTask::__construct()
     */
    public function addCallback(callable $callback, ?string $description = null): CallbackTask
    {
        return $this->add(new CallbackTask($callback, $description));
    }

    /**
     * @see ProcessTask::__construct()
     *
     * @param string|Process $process
     */
    public function addProcess($process): ProcessTask
    {
        return $this->add(new ProcessTask($process));
    }

    /**
     * @see PingTask::__construct()
     */
    public function addPing(string $url, string $method = 'GET', array $options = []): PingTask
    {
        return $this->add(new PingTask($url, $method, $options));
    }

    /**
     * @see MessageTask::__construct()
     */
    public function addMessage(object $message, array $stamps = []): MessageTask
    {
        return $this->add(new MessageTask($message, $stamps));
    }

    public function addCompound(): CompoundTask
    {
        return $this->add(new CompoundTask());
    }

    /**
     * Prevent schedule from running if callback throws \Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule.
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext
     */
    public function filter(callable $callback, ?string $description = null): self
    {
        return $this->addExtension(CallbackExtension::scheduleFilter($callback, $description));
    }

    /**
     * Only run schedule if true.
     *
     * @param bool|callable $callback bool: skip if false, callable: skip if return value is false
     *                                callable receives an instance of \Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext
     */
    public function when(string $description, $callback): self
    {
        $callback = \is_callable($callback) ? $callback : fn() => (bool) $callback;

        return $this->filter(function(ScheduleRunContext $context) use ($callback, $description) {
            if (!$callback($context)) {
                throw new SkipSchedule($description);
            }
        });
    }

    /**
     * Skip schedule if true.
     *
     * @param bool|callable $callback bool: skip if true, callable: skip if return value is true
     *                                callable receives an instance of \Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext
     */
    public function skip(string $description, $callback): self
    {
        $callback = \is_callable($callback) ? $callback : fn() => (bool) $callback;

        return $this->filter(function(ScheduleRunContext $context) use ($callback, $description) {
            if ($callback($context)) {
                throw new SkipSchedule($description);
            }
        }, $description);
    }

    /**
     * Execute callback before tasks run (even if no tasks are due).
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext
     */
    public function before(callable $callback, ?string $description = null): self
    {
        return $this->addExtension(CallbackExtension::scheduleBefore($callback, $description));
    }

    /**
     * Execute callback after tasks run (even if no tasks ran).
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext
     */
    public function after(callable $callback, ?string $description = null): self
    {
        return $this->addExtension(CallbackExtension::scheduleAfter($callback, $description));
    }

    /**
     * Alias for after().
     */
    public function then(callable $callback): self
    {
        return $this->after($callback);
    }

    /**
     * Execute callback after tasks run if all tasks succeeded
     *  - even if no tasks ran
     *  - skipped tasks are considered successful.
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext
     */
    public function onSuccess(callable $callback, ?string $description = null): self
    {
        return $this->addExtension(CallbackExtension::scheduleSuccess($callback, $description));
    }

    /**
     * Execute callback after tasks run if one or more tasks failed
     *  - skipped tasks are considered successful.
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext
     */
    public function onFailure(callable $callback, ?string $description = null): self
    {
        return $this->addExtension(CallbackExtension::scheduleFailure($callback, $description));
    }

    /**
     * Ping a webhook before any tasks run (even if none are due).
     * If you want to control the HttpClient used, configure `zenstruck_schedule.http_client`.
     *
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    public function pingBefore(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->addExtension(PingExtension::scheduleBefore($url, $method, $options));
    }

    /**
     * Ping a webhook after tasks ran (even if none ran).
     * If you want to control the HttpClient used, configure `zenstruck_schedule.http_client`.
     *
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    public function pingAfter(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->addExtension(PingExtension::scheduleAfter($url, $method, $options));
    }

    /**
     * Alias for pingAfter().
     */
    public function thenPing(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->pingAfter($url, $method, $options);
    }

    /**
     * Ping a webhook after tasks run if all tasks succeeded (skipped tasks are considered successful).
     * If you want to control the HttpClient used, configure `zenstruck_schedule.http_client`.
     *
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    public function pingOnSuccess(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->addExtension(PingExtension::scheduleSuccess($url, $method, $options));
    }

    /**
     * Ping a webhook after tasks run if one or more tasks failed.
     * If you want to control the HttpClient used, configure `zenstruck_schedule.http_client`.
     *
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    public function pingOnFailure(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->addExtension(PingExtension::scheduleFailure($url, $method, $options));
    }

    /**
     * Email failed task detail after tasks run if one or more tasks failed.
     * Be sure to configure `zenstruck_schedule.mailer`.
     *
     * @param string|string[] $to       Email address(es)
     * @param callable|null   $callback Add your own headers etc
     *                                  Receives an instance of \Symfony\Component\Mime\Email
     */
    public function emailOnFailure($to = null, ?string $subject = null, ?callable $callback = null): self
    {
        return $this->addExtension(EmailExtension::scheduleFailure($to, $subject, $callback));
    }

    /**
     * Send notification with failed task detail after tasks run if one or more tasks failed.
     * Be sure to configure `zenstruck_schedule.notifier`.
     *
     * @param string|string[] $channel  Channel to send notification to (E.G "chat/slack")
     * @param string|null     $email    Email address for email notification
     * @param string|null     $phone    Phone number for SMS notification
     * @param callable|null   $callback Customise the notification
     *                                  Receives an instance of \Symfony\Component\Notification\Notification
     */
    public function notifyOnFailure($channel = null, ?string $email = null, ?string $phone = null, ?string $subject = null, ?callable $callback = null): self
    {
        return $this->addExtension(NotifierExtension::scheduleFailure($channel, $email, $phone, $subject, $callback));
    }

    /**
     * Restrict running of schedule to a single server.
     * Be sure to configure `zenstruck_schedule.single_server_lock_factory`.
     *
     * @param int $ttl Maximum expected lock duration in seconds
     */
    public function onSingleServer(int $ttl = SingleServerExtension::DEFAULT_TTL): self
    {
        return $this->addExtension(new SingleServerExtension($ttl));
    }

    /**
     * Define the application environment(s) you wish to run the schedule in. Trying to
     * run in another environment will skip the schedule.
     */
    public function environments(string $environment, string ...$environments): self
    {
        return $this->addExtension(new EnvironmentExtension(\array_merge([$environment], $environments)));
    }

    /**
     * The default timezone for tasks (tasks can override).
     *
     * @param string|\DateTimeZone $value
     */
    public function timezone($value): self
    {
        $this->resetCache();

        if (!$value instanceof \DateTimeZone) {
            $value = new \DateTimeZone($value);
        }

        $this->timezone = $value;

        return $this;
    }

    public function getTimezone(): ?\DateTimeZone
    {
        return $this->timezone;
    }

    public function getTask(string $id): Task
    {
        // check for duplicated task ids
        $tasks = [];

        foreach ($this->all() as $task) {
            $tasks[$task->getId()][] = $task;
        }

        if (!\array_key_exists($id, $tasks)) {
            throw new \InvalidArgumentException("Task with ID \"{$id}\" not found.");
        }

        if (1 !== $count = \count($tasks[$id])) {
            throw new \RuntimeException(\sprintf('Task ID "%s" is ambiguous, there are %d tasks this id.', $id, $count));
        }

        return $tasks[$id][0];
    }

    /**
     * @return Task[]
     */
    public function all(): array
    {
        if (null !== $this->allTasks) {
            return $this->allTasks;
        }

        $this->allTasks = [];

        foreach ($this->taskIterator() as $task) {
            if ($this->getTimezone() && !$task->getTimezone()) {
                $task->timezone($this->getTimezone());
            }

            $this->allTasks[] = $task;
        }

        return $this->allTasks;
    }

    /**
     * @return Task[]
     */
    public function due(\DateTimeInterface $timestamp): array
    {
        if (null !== $this->dueTasks) {
            return $this->dueTasks;
        }

        $this->dueTasks = [];

        foreach ($this->all() as $task) {
            if ($task->isDue($timestamp)) {
                $this->dueTasks[] = $task;
            }
        }

        return $this->dueTasks;
    }

    /**
     * @return Task[]
     */
    private function taskIterator(): iterable
    {
        foreach ($this->tasks as $task) {
            if ($task instanceof CompoundTask) {
                yield from $task;

                continue;
            }

            yield $task;
        }
    }

    private function resetCache(): void
    {
        $this->allTasks = $this->dueTasks = null;
    }
}
