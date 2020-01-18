<?php

namespace Zenstruck\ScheduleBundle\Schedule;

use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension\BetweenTimeExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\CallbackExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\EmailExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\PingExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\WithoutOverlappingExtension;

/**
 * @author Taylor Otwell <taylor@laravel.com>
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Task
{
    private const DEFAULT_EXPRESSION = '* * * * *';

    private $description;
    private $expression = self::DEFAULT_EXPRESSION;
    private $timezone;

    /** @var Extension[] */
    private $extensions = [];

    public function __construct(string $description)
    {
        $this->description = $description;
    }

    final public function __toString(): string
    {
        return $this->getDescription();
    }

    public function getType(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    final public function getId(): string
    {
        return \sha1(\get_class($this).$this->getExpression().$this->getDescription());
    }

    final public function getDescription(): string
    {
        return $this->description;
    }

    final public function getExpression(): CronExpression
    {
        return new CronExpression($this->expression, $this->getDescription());
    }

    final public function getTimezone(): ?\DateTimeZone
    {
        return $this->timezone;
    }

    final public function getNextRun(): \DateTimeInterface
    {
        return $this->getExpression()->getNextRun($this->getTimezoneValue());
    }

    final public function isDue(): bool
    {
        return $this->getExpression()->isDue($this->getTimezoneValue());
    }

    /**
     * @return Extension[]
     */
    final public function getExtensions(): array
    {
        return $this->extensions;
    }

    final public function addExtension(Extension $extension): self
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /**
     * Set a unique description for this task.
     */
    final public function description(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    /**
     * The timezone this task should run in.
     *
     * @param string|\DateTimeZone $value
     */
    final public function timezone($value): self
    {
        if (!$value instanceof \DateTimeZone) {
            $value = new \DateTimeZone($value);
        }

        $this->timezone = $value;

        return $this;
    }

    /**
     * Prevent task from running if callback throws \Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask.
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeTaskEvent
     */
    final public function filter(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::taskFilter($callback));
    }

    /**
     * Only run task if true.
     *
     * @param bool|callable $callback bool: skip if false, callable: skip if return value is false
     *                                callable receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeTaskEvent
     */
    final public function when(string $description, $callback): self
    {
        $callback = \is_callable($callback) ? $callback : function () use ($callback) {
            return (bool) $callback;
        };

        return $this->filter(function (BeforeTaskEvent $event) use ($callback, $description) {
            if (!$callback($event)) {
                throw new SkipTask($description);
            }
        });
    }

    /**
     * Skip task if true.
     *
     * @param bool|callable $callback bool: skip if true, callable: skip if return value is true
     *                                callable receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeTaskEvent
     */
    final public function skip(string $description, $callback): self
    {
        $callback = \is_callable($callback) ? $callback : function () use ($callback) {
            return (bool) $callback;
        };

        return $this->filter(function (BeforeTaskEvent $event) use ($callback, $description) {
            if ($callback($event)) {
                throw new SkipTask($description);
            }
        });
    }

    /**
     * Execute callback before task runs.
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeTaskEvent
     */
    final public function before(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::taskBefore($callback));
    }

    /**
     * Execute callback after task has run (will not execute if skipped).
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\AfterTaskEvent
     */
    final public function after(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::taskAfter($callback));
    }

    /**
     * Alias for after().
     */
    final public function then(callable $callback): self
    {
        return $this->after($callback);
    }

    /**
     * Execute callback if task was successful (will not execute if skipped).
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\AfterTaskEvent
     */
    final public function onSuccess(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::taskSuccess($callback));
    }

    /**
     * Execute callback if task failed (will not execute if skipped).
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\AfterTaskEvent
     */
    final public function onFailure(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::taskFailure($callback));
    }

    /**
     * Ping a webhook before task runs (will not ping if task was skipped).
     * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
     *
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    final public function pingBefore(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->addExtension(PingExtension::taskBefore($url, $method, $options));
    }

    /**
     * Ping a webhook after task has run (will not ping if task was skipped).
     * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
     *
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    final public function pingAfter(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->addExtension(PingExtension::taskAfter($url, $method, $options));
    }

    /**
     * Alias for pingAfter().
     */
    final public function thenPing(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->pingAfter($url, $method, $options);
    }

    /**
     * Ping a webhook if task was successful (will not ping if task was skipped).
     * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
     *
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    final public function pingOnSuccess(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->addExtension(PingExtension::taskSuccess($url, $method, $options));
    }

    /**
     * Ping a webhook if task failed (will not ping if task was skipped).
     * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
     *
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    final public function pingOnFailure(string $url, string $method = 'GET', array $options = []): self
    {
        return $this->addExtension(PingExtension::taskFailure($url, $method, $options));
    }

    /**
     * Email task detail after run (on success or failure, not if skipped).
     * Be sure to configure `zenstruck_schedule.email_handler`.
     *
     * @param string|string[] $to       Email address(es)
     * @param callable|null   $callback Add your own headers etc
     *                                  Receives an instance of \Symfony\Component\Mime\Email
     */
    final public function emailAfter($to = null, string $subject = null, callable $callback = null): self
    {
        return $this->addExtension(EmailExtension::taskAfter($to, $subject, $callback));
    }

    /**
     * Alias for emailAfter().
     */
    final public function thenEmail($to = null, string $subject = null, callable $callback = null): self
    {
        return $this->emailAfter($to, $subject, $callback);
    }

    /**
     * Email task/failure details if failed (not if skipped).
     * Be sure to configure `zenstruck_schedule.email_handler`.
     *
     * @param string|string[] $to       Email address(es)
     * @param callable|null   $callback Add your own headers etc
     *                                  Receives an instance of \Symfony\Component\Mime\Email
     */
    final public function emailOnFailure($to = null, string $subject = null, callable $callback = null): self
    {
        return $this->addExtension(EmailExtension::taskFailure($to, $subject, $callback));
    }

    /**
     * Prevent task from running if still running from previous run.
     *
     * @param int $ttl Maximum expected lock duration in seconds
     */
    final public function withoutOverlapping(int $ttl = WithoutOverlappingExtension::DEFAULT_TTL): self
    {
        return $this->addExtension(new WithoutOverlappingExtension($ttl));
    }

    /**
     * Restrict running of schedule to a single server.
     * Be sure to configure `zenstruck_schedule.single_server_handler`.
     *
     * @param int $ttl Maximum expected lock duration in seconds
     */
    final public function onSingleServer(int $ttl = SingleServerExtension::DEFAULT_TTL): self
    {
        return $this->addExtension(new SingleServerExtension($ttl));
    }

    /**
     * Only run between given times.
     *
     * @param string $startTime "HH:MM" (ie "09:00")
     * @param string $endTime   "HH:MM" (ie "14:30")
     * @param bool   $inclusive Whether to include the start and end time
     */
    final public function between(string $startTime, string $endTime, bool $inclusive = true): self
    {
        return $this->addExtension(BetweenTimeExtension::whenWithin($startTime, $endTime, $inclusive));
    }

    /**
     * Skip when between given times.
     *
     * @param string $startTime "HH:MM" (ie "09:00")
     * @param string $endTime   "HH:MM" (ie "14:30")
     * @param bool   $inclusive Whether to include the start and end time
     */
    final public function unlessBetween(string $startTime, string $endTime, bool $inclusive = true): self
    {
        return $this->addExtension(BetweenTimeExtension::unlessWithin($startTime, $endTime, $inclusive));
    }

    /**
     * Set your own cron expression (ie "15 3 * * 1,4").
     */
    final public function cron(string $expression): self
    {
        $this->expression = $expression;

        return $this;
    }

    final public function everyMinute(): self
    {
        return $this->spliceIntoPosition(1, '*');
    }

    final public function everyFiveMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/5');
    }

    final public function everyTenMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/10');
    }

    final public function everyFifteenMinutes(): self
    {
        return $this->spliceIntoPosition(1, '*/15');
    }

    final public function everyThirtyMinutes(): self
    {
        return $this->spliceIntoPosition(1, '0,30');
    }

    final public function hourly(): self
    {
        return $this->spliceIntoPosition(1, 0);
    }

    /**
     * @param int $minute 0-59
     */
    final public function hourlyAt(int $minute): self
    {
        return $this->spliceIntoPosition(1, $minute);
    }

    final public function daily(): self
    {
        return $this
            ->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
        ;
    }

    /**
     * @param string $time "HH:MM" (ie "14:30")
     */
    final public function at(string $time): self
    {
        $segments = \explode(':', $time);

        return $this
            ->spliceIntoPosition(2, (int) $segments[0])
            ->spliceIntoPosition(1, 2 === \count($segments) ? (int) $segments[1] : '0')
        ;
    }

    /**
     * Alias for at().
     */
    final public function dailyAt(string $time): self
    {
        return $this->at($time);
    }

    /**
     * @param int $firstHour  0-23
     * @param int $secondHour 0-23
     */
    final public function twiceDaily(int $firstHour = 1, int $secondHour = 13): self
    {
        return $this
            ->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, $firstHour.','.$secondHour)
        ;
    }

    final public function weekdays(): self
    {
        return $this->spliceIntoPosition(5, '1-5');
    }

    final public function weekends(): self
    {
        return $this->spliceIntoPosition(5, '0,6');
    }

    /**
     * @param int ...$days 0 = Sunday, 6 = Saturday
     */
    final public function days(int ...$days): self
    {
        return $this->spliceIntoPosition(5, \implode(',', $days));
    }

    final public function mondays(): self
    {
        return $this->days(1);
    }

    final public function tuesdays(): self
    {
        return $this->days(2);
    }

    final public function wednesdays(): self
    {
        return $this->days(3);
    }

    final public function thursdays(): self
    {
        return $this->days(4);
    }

    final public function fridays(): self
    {
        return $this->days(5);
    }

    final public function saturdays(): self
    {
        return $this->days(6);
    }

    final public function sundays(): self
    {
        return $this->days(0);
    }

    final public function weekly(): self
    {
        return $this
            ->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(5, 0)
        ;
    }

    final public function monthly(): self
    {
        return $this
            ->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 1)
        ;
    }

    /**
     * @param int    $day  1-31
     * @param string $time "HH:MM" (ie "14:30")
     */
    final public function monthlyOn(int $day, string $time = '0:0'): self
    {
        return $this
            ->dailyAt($time)
            ->spliceIntoPosition(3, $day)
        ;
    }

    /**
     * @param int $firstDay  1-31
     * @param int $secondDay 1-31
     */
    final public function twiceMonthly(int $firstDay = 1, int $secondDay = 16): self
    {
        return $this
            ->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, $firstDay.','.$secondDay)
        ;
    }

    final public function quarterly(): self
    {
        return $this
            ->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 1)
            ->spliceIntoPosition(4, '1-12/3')
        ;
    }

    final public function yearly(): self
    {
        return $this
            ->spliceIntoPosition(1, 0)
            ->spliceIntoPosition(2, 0)
            ->spliceIntoPosition(3, 1)
            ->spliceIntoPosition(4, 1)
        ;
    }

    private function spliceIntoPosition(int $position, string $value): self
    {
        $segments = \explode(' ', $this->expression);

        if (5 !== \count($segments)) { // reset if set to alias or invalid
            $this->expression = self::DEFAULT_EXPRESSION;

            return $this->spliceIntoPosition($position, $value);
        }

        $segments[$position - 1] = $value;

        return $this->cron(\implode(' ', $segments));
    }

    private function getTimezoneValue(): ?string
    {
        return $this->getTimezone() ? $this->getTimezone()->getName() : null;
    }
}
