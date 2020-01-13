<?php

namespace Zenstruck\ScheduleBundle;

use Symfony\Component\Process\Process;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\CallbackExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\EmailExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\EnvironmentExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\PingExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\CallbackTask;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;
use Zenstruck\ScheduleBundle\Schedule\Task\CompoundTask;
use Zenstruck\ScheduleBundle\Schedule\Task\ProcessTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Schedule
{
    private $tasks = [];
    private $allTasks;
    private $dueTasks;
    private $timezone;

    /** @var Extension[] */
    private $extensions = [];

    public function getId(): string
    {
        $tasks = \array_map(
            function (Task $task) {
                return $task->getId();
            },
            $this->all()
        );

        return \sha1(\implode('', $tasks));
    }

    public function add(Task $task): Task
    {
        $this->allTasks = null;
        $this->dueTasks = null;

        return $this->tasks[] = $task;
    }

    /**
     * @param string $name Command class or name (my:command)
     */
    public function addCommand(string $name, string ...$arguments): CommandTask
    {
        return $this->add(new CommandTask($name, ...$arguments));
    }

    /**
     * @param callable $callback Return value is considered "output"
     */
    public function addCallback(callable $callback): CallbackTask
    {
        return $this->add(new CallbackTask($callback));
    }

    /**
     * @param string|Process $process
     */
    public function addProcess($process): ProcessTask
    {
        return $this->add(new ProcessTask($process));
    }

    public function addCompound(): CompoundTask
    {
        return $this->add(new CompoundTask());
    }

    public function addExtension(Extension $extension): self
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /**
     * Prevent schedule from running if callback throws \Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule.
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent
     */
    public function filter(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::scheduleFilter($callback));
    }

    /**
     * Only run schedule if true.
     *
     * @param bool|callable $callback bool: skip if false, callable: skip if return value is false
     *                                callable receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent
     */
    public function when(string $description, $callback): self
    {
        $callback = \is_callable($callback) ? $callback : function () use ($callback) {
            return (bool) $callback;
        };

        return $this->filter(function (BeforeScheduleEvent $event) use ($callback, $description) {
            if (!$callback($event)) {
                throw new SkipSchedule($description);
            }
        });
    }

    /**
     * Skip schedule if true.
     *
     * @param bool|callable $callback bool: skip if true, callable: skip if return value is true
     *                                callable receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent
     */
    public function skip(string $description, $callback): self
    {
        $callback = \is_callable($callback) ? $callback : function () use ($callback) {
            return (bool) $callback;
        };

        return $this->filter(function (BeforeScheduleEvent $event) use ($callback, $description) {
            if ($callback($event)) {
                throw new SkipSchedule($description);
            }
        });
    }

    /**
     * Execute callback before tasks run (even if no tasks are due).
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent
     */
    public function before(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::scheduleBefore($callback));
    }

    /**
     * Execute callback after tasks run (even if no tasks ran).
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\AfterScheduleEvent
     */
    public function after(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::scheduleAfter($callback));
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
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\AfterScheduleEvent
     */
    public function onSuccess(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::scheduleSuccess($callback));
    }

    /**
     * Execute callback after tasks run if one or more tasks failed
     *  - skipped tasks are considered successful.
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\AfterScheduleEvent
     */
    public function onFailure(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::scheduleFailure($callback));
    }

    /**
     * Ping a webhook before any tasks run (even if none are due).
     * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
     *
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    public function pingBefore(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->addExtension(PingExtension::scheduleBefore($url, $method, $options));
    }

    /**
     * Ping a webhook after tasks ran (even if none ran).
     * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
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
     * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
     *
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    public function pingOnSuccess(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->addExtension(PingExtension::scheduleSuccess($url, $method, $options));
    }

    /**
     * Ping a webhook after tasks run if one or more tasks failed.
     * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
     *
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    public function pingOnFailure(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->addExtension(PingExtension::scheduleFailure($url, $method, $options));
    }

    /**
     * Email failed task detail after tasks run if one or more tasks failed.
     * Be sure to configure `zenstruck_schedule.email_handler`.
     *
     * @param string|string[] $to       Email address(es)
     * @param callable|null   $callback Add your own headers etc
     *                                  Receives an instance of \Symfony\Component\Mime\Email
     */
    public function emailOnFailure($to = null, string $subject = null, callable $callback = null): self
    {
        return $this->addExtension(EmailExtension::scheduleFailure($to, $subject, $callback));
    }

    /**
     * Restrict running of schedule to a single server.
     * Be sure to configure `zenstruck_schedule.single_server_handler`.
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
    public function environments(string ...$environments): self
    {
        return $this->addExtension(new EnvironmentExtension($environments));
    }

    /**
     * The default timezone for tasks (tasks can override).
     *
     * @param string|\DateTimeZone $value
     */
    public function timezone($value): self
    {
        if (!$value instanceof \DateTimeZone) {
            $value = new \DateTimeZone($value);
        }

        $this->timezone = $value;

        return $this;
    }

    /**
     * @return Extension[]
     */
    public function getExtensions(): array
    {
        return $this->extensions;
    }

    public function getTimezone(): ?\DateTimeZone
    {
        return $this->timezone;
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

        foreach ($this->tasks as $task) {
            if ($task instanceof CompoundTask) {
                foreach ($task as $subTask) {
                    $this->allTasks[] = $subTask;
                }

                continue;
            }

            $this->allTasks[] = $task;
        }

        if ($timezone = $this->getTimezone()) {
            foreach ($this->allTasks as $task) {
                if (!$task->getTimezone()) {
                    $task->timezone($timezone);
                }
            }
        }

        return $this->allTasks;
    }

    /**
     * @return Task[]
     */
    public function due(): array
    {
        if (null !== $this->dueTasks) {
            return $this->dueTasks;
        }

        $this->dueTasks = [];

        foreach ($this->all() as $task) {
            if ($task->isDue()) {
                $this->dueTasks[] = $task;
            }
        }

        return $this->dueTasks;
    }
}
