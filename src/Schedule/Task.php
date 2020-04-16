<?php

namespace Zenstruck\ScheduleBundle\Schedule;

use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension\BetweenTimeExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\CallbackExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\EmailExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\PingExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\WithoutOverlappingExtension;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Taylor Otwell <taylor@laravel.com>
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class Task
{
    use HasExtensions;

    private const DEFAULT_EXPRESSION = '* * * * *';

    private $description;
    private $expression = self::DEFAULT_EXPRESSION;
    private $timezone;

    public function __construct(string $description)
    {
        $this->description = $description;
    }

    final public function __toString(): string
    {
        return "{$this->getType()}: {$this->getDescription()}";
    }

    public function getType(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    final public function getId(): string
    {
        return \sha1(static::class.$this->getExpression().$this->getDescription());
    }

    final public function getDescription(): string
    {
        return $this->description;
    }

    public function getContext(): array
    {
        return [];
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

    final public function isDue(\DateTimeInterface $timestamp): bool
    {
        return $this->getExpression()->isDue($timestamp, $this->getTimezoneValue());
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
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext
     */
    final public function filter(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::taskFilter($callback));
    }

    /**
     * Only run task if true.
     *
     * @param bool|callable $callback bool: skip if false, callable: skip if return value is false
     *                                callable receives an instance of \Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext
     */
    final public function when(string $description, $callback): self
    {
        $callback = \is_callable($callback) ? $callback : function () use ($callback) {
            return (bool) $callback;
        };

        return $this->filter(function (TaskRunContext $context) use ($callback, $description) {
            if (!$callback($context)) {
                throw new SkipTask($description);
            }
        });
    }

    /**
     * Skip task if true.
     *
     * @param bool|callable $callback bool: skip if true, callable: skip if return value is true
     *                                callable receives an instance of \Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext
     */
    final public function skip(string $description, $callback): self
    {
        $callback = \is_callable($callback) ? $callback : function () use ($callback) {
            return (bool) $callback;
        };

        return $this->filter(function (TaskRunContext $context) use ($callback, $description) {
            if ($callback($context)) {
                throw new SkipTask($description);
            }
        });
    }

    /**
     * Execute callback before task runs.
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext
     */
    final public function before(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::taskBefore($callback));
    }

    /**
     * Execute callback after task has run (will not execute if skipped).
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext
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
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext
     */
    final public function onSuccess(callable $callback): self
    {
        return $this->addExtension(CallbackExtension::taskSuccess($callback));
    }

    /**
     * Execute callback if task failed (will not execute if skipped).
     *
     * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext
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
    final public function onlyBetween(string $startTime, string $endTime, bool $inclusive = true): self
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

    /**
     * Resets the expression to "* * * * *".
     */
    final public function everyMinute(): self
    {
        return $this->cron(self::DEFAULT_EXPRESSION);
    }

    /**
     * Resets the expression to "<*>/5 * * * *".
     */
    final public function everyFiveMinutes(): self
    {
        return $this->cron('*/5 * * * *');
    }

    /**
     * Resets the expression to "<*>/10 * * * *".
     */
    final public function everyTenMinutes(): self
    {
        return $this->cron('*/10 * * * *');
    }

    /**
     * Resets the expression to "<*>/15 * * * *".
     */
    final public function everyFifteenMinutes(): self
    {
        return $this->cron('*/15 * * * *');
    }

    /**
     * Resets the expression to "<*>/20 * * * *".
     */
    final public function everyTwentyMinutes(): self
    {
        return $this->cron('*/20 * * * *');
    }

    /**
     * Resets the expression to "0,30 * * * *".
     */
    final public function everyThirtyMinutes(): self
    {
        return $this->cron('0,30 * * * *');
    }

    /**
     * Resets the expression to "0 * * * *".
     */
    final public function hourly(): self
    {
        return $this->cron('0 * * * *');
    }

    /**
     * Resets the expression to "X * * * *" with X being the passed minute(s).
     *
     * @param int|string $minute     Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (20/2)
     * @param int|string ...$minutes Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (20/2)
     */
    final public function hourlyAt($minute, ...$minutes): self
    {
        return $this->hourly()->minutes($minute, ...$minutes);
    }

    /**
     * Resets the expression to "0 0 * * *".
     */
    final public function daily(): self
    {
        return $this->cron('0 0 * * *');
    }

    /**
     * Resets the expression to "0 X * * *" with X being the passed hour(s).
     *
     * @param int|string $hour     Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (1-5/2)
     * @param int|string ...$hours Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (1-5/2)
     */
    final public function dailyOn($hour, ...$hours): self
    {
        return $this->daily()->hours($hour, ...$hours);
    }

    /**
     * Resets the expression to "0 X-Y * * *" with X and Y being the passed start and end hours.
     *
     * @param int $firstHour  0-23
     * @param int $secondHour 0-23
     */
    final public function dailyBetween(int $firstHour, int $secondHour): self
    {
        return $this->daily()->hours("{$firstHour}-{$secondHour}");
    }

    /**
     * Resets the expression to "0 X,Y * * *" with X and Y being the passed hours.
     *
     * @param int $firstHour  0-23
     * @param int $secondHour 0-23
     */
    final public function twiceDaily(int $firstHour = 1, int $secondHour = 13): self
    {
        return $this->dailyOn($firstHour, $secondHour);
    }

    /**
     * Shortcut for ->daily()->at($time).
     *
     * @param string $time Integer for just the hour (ie 2) or "HH:MM" for hour and minute (ie "14:30")
     */
    final public function dailyAt(string $time): self
    {
        return $this->daily()->at($time);
    }

    /**
     * Resets the expression to "0 0 * * 0".
     */
    final public function weekly(): self
    {
        return $this->cron('0 0 * * 0');
    }

    /**
     * Resets the expression to "0 0 * * X" with X being the passed day(s) of week.
     *
     * @param int|string $day     Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (1-5/2)
     * @param int|string ...$days Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (1-5/2)
     */
    final public function weeklyOn($day, ...$days): self
    {
        return $this->weekly()->daysOfWeek($day, ...$days);
    }

    /**
     * Resets the expression to "0 0 1 * *".
     */
    final public function monthly(): self
    {
        return $this->cron('0 0 1 * *');
    }

    /**
     * Resets the expression to "0 0 X * *" with X being the passed day(s) of month.
     *
     * @param int|string $day     Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (20/2)
     * @param int|string ...$days Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (20/2)
     */
    final public function monthlyOn($day, ...$days): self
    {
        return $this->monthly()->daysOfMonth($day, ...$days);
    }

    /**
     * Resets the expression to "0 0 X,Y * *" with X and Y being the passed days of the month.
     *
     * @param int $firstDay  1-31
     * @param int $secondDay 1-31
     */
    final public function twiceMonthly(int $firstDay = 1, int $secondDay = 16): self
    {
        return $this->monthlyOn($firstDay, $secondDay);
    }

    /**
     * Resets the expression to "0 0 1 1 *".
     */
    final public function yearly(): self
    {
        return $this->cron('0 0 1 1 *');
    }

    /**
     * Resets the expression to "0 0 1 1-12/3 *".
     */
    final public function quarterly(): self
    {
        return $this->cron('0 0 1 */3 *');
    }

    /**
     * Set just the "minute" field.
     *
     * @param int|string $minute     Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (20/2)
     * @param int|string ...$minutes Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (20/2)
     */
    final public function minutes($minute, ...$minutes): self
    {
        return $this->spliceIntoPosition(CronExpression::MINUTE, $minute, ...$minutes);
    }

    /**
     * Set just the "hour" field.
     *
     * @param int|string $hour     Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (20/2)
     * @param int|string ...$hours Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (20/2)
     */
    final public function hours($hour, ...$hours): self
    {
        return $this->spliceIntoPosition(CronExpression::HOUR, $hour, ...$hours);
    }

    /**
     * Set just the "day of month" field.
     *
     * @param int|string $day     Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (20/2)
     * @param int|string ...$days Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (20/2)
     */
    final public function daysOfMonth($day, ...$days): self
    {
        return $this->spliceIntoPosition(CronExpression::DOM, $day, ...$days);
    }

    /**
     * Set just the "month" field.
     *
     * @param int|string $month     Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (1-12/3)
     * @param int|string ...$months Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (1-12/3)
     */
    final public function months($month, ...$months): self
    {
        return $this->spliceIntoPosition(CronExpression::MONTH, $month, ...$months);
    }

    /**
     * Set just the "day of week" field.
     *
     * @param int|string $day     Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (1-5/2)
     * @param int|string ...$days Single value (ie 1), multiple values (ie 1,3), range (ie 1-3), or step values (1-5/2)
     */
    final public function daysOfWeek($day, ...$days): self
    {
        return $this->spliceIntoPosition(CronExpression::DOW, $day, ...$days);
    }

    /**
     * Set just the "day of week" field.
     */
    final public function weekdays(): self
    {
        return $this->daysOfWeek('1-5');
    }

    /**
     * Set just the "day of week" field.
     */
    final public function weekends(): self
    {
        return $this->daysOfWeek(0, 6);
    }

    /**
     * Set just the "day of week" field.
     */
    final public function mondays(): self
    {
        return $this->daysOfWeek(1);
    }

    /**
     * Set just the "day of week" field.
     */
    final public function tuesdays(): self
    {
        return $this->daysOfWeek(2);
    }

    /**
     * Set just the "day of week" field.
     */
    final public function wednesdays(): self
    {
        return $this->daysOfWeek(3);
    }

    /**
     * Set just the "day of week" field.
     */
    final public function thursdays(): self
    {
        return $this->daysOfWeek(4);
    }

    /**
     * Set just the "day of week" field.
     */
    final public function fridays(): self
    {
        return $this->daysOfWeek(5);
    }

    /**
     * Set just the "day of week" field.
     */
    final public function saturdays(): self
    {
        return $this->daysOfWeek(6);
    }

    /**
     * Set just the "day of week" field.
     */
    final public function sundays(): self
    {
        return $this->daysOfWeek(0);
    }

    /**
     * Set just the "hour" and optionally the "minute" field(s).
     *
     * @param string $time Integer for just the hour (ie 2) or "HH:MM" for hour and minute (ie "14:30")
     */
    final public function at(string $time): self
    {
        $segments = \explode(':', $time);

        return $this
            ->hours($segments[0])
            ->minutes(2 === \count($segments) ? $segments[1] : 0)
        ;
    }

    /**
     * @param int|string $value
     * @param int|string ...$values
     */
    private function spliceIntoPosition(int $position, $value, ...$values): self
    {
        $segments = \explode(' ', $this->expression);

        if (5 !== \count($segments)) { // reset if set to alias or invalid
            $this->expression = self::DEFAULT_EXPRESSION;

            return $this->spliceIntoPosition($position, $value);
        }

        $segments[$position] = \implode(',', \array_merge([$value], $values));

        return $this->cron(\implode(' ', $segments));
    }

    private function getTimezoneValue(): ?string
    {
        return $this->getTimezone() ? $this->getTimezone()->getName() : null;
    }
}
