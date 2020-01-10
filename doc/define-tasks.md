# Defining Tasks

Tasks are defined by adding them to your schedule. See [Defining the Schedule](define-schedule.md)
to see where to add these.

## Task Types

### CommandTask

This task runs a Symfony console command.

```php
/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->addCommand('my:command', 'arg1', '--option1', '--option2=value');

// alternative
$schedule->addCommand(\App\Command\MyCommand::class)
    ->arguments('arg1', '--option1', '--option2=value')
;
```

### CallbackTask

This task runs a callback. The optional return value of the callback is considered the
task "output".

```php
/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->addCallback(function () {
    // do something
    
    return 'task output';
});
```

### ProcessTask

This task executes shell commands. `symfony/process` is required.

```php
/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->addProcess('/bin/my-script');

// alternatively, add your own Process instance
$process = new \Symfony\Component\Process\Process(['/bin/my-script']);
$process->setWorkingDirectory('/home/user');
$process->setTimeout(10);

$schedule->addProcess($process);
```

### CompoundTask

This is a special task that allows you to group other tasks together that share a
frequency, timezone and hooks.

```php
/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->addCompound()
    ->addCommand('my:command')
    ->addCallback(function () { /* do something */ })
    ->addProcess('/bin/my-script')
    ->mondays()
    ->at('1:30')
    ->timezone('UTC')
    ->emailOnFailure('admin@example.com')
;
```

## Task Description

Optionally add a unique description to your task. If none is provided, tasks define a
default description based on their input.

```php
/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->addCommand('my:command')
    ->description('this describes my task')
;
```

## Frequency Options

These are the options for defining how often your task runs:

```php
public function everyMinute()

public function everyFiveMinutes()

public function everyTenMinutes()

public function everyFifteenMinutes()

public function everyThirtyMinutes()

public function hourly()

/**
 * @param int $minute 0-59
 */
public function hourlyAt(int $minute)

public function daily()

/**
 * @param string $time "HH:MM" (ie "14:30")
 */
public function at(string $time)

/**
 * Alias for at().
 */
public function dailyAt(string $time)

/**
 * @param int $firstHour  0-23
 * @param int $secondHour 0-23
 */
public function twiceDaily(int $firstHour = 1, int $secondHour = 13)

public function weekdays()

public function weekends()

/**
 * @param int ...$days 0 = Sunday, 6 = Saturday
 */
public function days(int ...$days)

public function mondays()

public function tuesdays()

public function wednesdays()

public function thursdays()

public function fridays()

public function saturdays()

public function sundays()

public function weekly()

public function monthly()

/**
 * @param int    $day  1-31
 * @param string $time "HH:MM" (ie "14:30")
 */
public function monthlyOn(int $day, string $time = '0:0')

/**
 * @param int $firstDay  1-31
 * @param int $secondDay 1-31
 */
public function twiceMonthly(int $firstDay = 1, int $secondDay = 16)

public function quarterly()

public function yearly()

/**
 * Set your own cron expression (ie "15 3 * * 1,4").
 */
public function cron(string $expression)
```

## Timezone

You may optionally define the timezone to use when determining when to
run a task. If none is provided, it will use PHP's default timezone.

```php
/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->addCommand('my:command')
    ->mondays()
    ->at('1:30')
    ->timezone('UTC')
;
```

Alternatively, you can configure the timezone for all tasks (timezone defined
on task will take precedence):

```yaml
# config/packages/zenstruck_schedule.yaml
zenstruck_schedule:
    timezone: UTC
```

## Task Hooks

The following hooks are available when defining a task:

### Filters

```php
/**
 * Prevent task from running if callback throws \Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask.
 *
 * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeTaskEvent
 */
public function filter(callable $callback)

/**
 * Only run task if true.
 *
 * @param bool|callable $callback bool: skip if false, callable: skip if return value is false
 *                                callable receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeTaskEvent
 */
public function when(string $description, $callback)

/**
 * Skip task if true.
 *
 * @param bool|callable $callback bool: skip if true, callable: skip if return value is true
 *                                callable receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeTaskEvent
 */
public function skip(string $description, $callback)
```

### Callbacks

```php
/**
 * Execute callback before task runs.
 *
 * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeTaskEvent
 */
public function before(callable $callback)

/**
 * Execute callback after task has run (will not execute if skipped).
 *
 * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\AfterTaskEvent
 */
public function after(callable $callback)

/**
 * Alias for after().
 */
public function then(callable $callback)

/**
 * Execute callback if task was successful (will not execute if skipped).
 *
 * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\AfterTaskEvent
 */
public function onSuccess(callable $callback)

/**
 * Execute callback if task failed (will not execute if skipped).
 *
 * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\AfterTaskEvent
 */
public function onFailure(callable $callback)
```

### Ping Webhook

```php
/**
 * Ping a webhook before task runs (will not ping if task was skipped).
 * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
 *
 * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
 */
public function pingBefore(string $url, string $method = 'GET', array $options = [])

/**
 * Ping a webhook after task has run (will not ping if task was skipped).
 * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
 *
 * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
 */
public function pingAfter(string $url, string $method = 'GET', array $options = [])

/**
 * Alias for pingAfter().
 */
public function thenPing(string $url, string $method = 'GET', array $options = [])

/**
 * Ping a webhook if task was successful (will not ping if task was skipped).
 * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
 *
 * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
 */
public function pingOnSuccess(string $url, string $method = 'GET', array $options = [])

/**
 * Ping a webhook if task failed (will not ping if task was skipped).
 * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
 *
 * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
 */
public function pingOnFailure(string $url, string $method = 'GET', array $options = [])
```

### Email Output

```php
/**
 * Email task detail after run (on success or failure, not if skipped).
 * Be sure to configure `zenstruck_schedule.email_handler`.
 *
 * @param string|string[] $to       Email address(es)
 * @param callable|null   $callback Add your own headers etc
 *                                  Receives an instance of \Symfony\Component\Mime\Email
 */
public function emailAfter($to = null, string $subject = null, callable $callback = null)

/**
 * Alias for emailAfter().
 */
public function thenEmail($to = null, string $subject = null, callable $callback = null)

/**
 * Email task/failure details if failed (not if skipped).
 * Be sure to configure `zenstruck_schedule.email_handler`.
 *
 * @param string|string[] $to       Email address(es)
 * @param callable|null   $callback Add your own headers etc
 *                                  Receives an instance of \Symfony\Component\Mime\Email
 */
public function emailOnFailure($to = null, string $subject = null, callable $callback = null)
```

### Prevent Overlap

```php
/**
 * Prevent task from running if still running from previous run.
 *
 * @param int $ttl Maximum expected lock duration in seconds
 */
public function withoutOverlapping(int $ttl = 3600)
```

### Run on Single Server

```php
/**
 * Restrict running of schedule to a single server.
 * Be sure to configure `zenstruck_schedule.single_server_handler`.
 *
 * @param int $ttl Maximum expected lock duration in seconds
 */
public function onSingleServer(int $ttl = 86400)
```

### Between

```php
/**
 * Only run between given times.
 *
 * @param string $startTime "HH:MM" (ie "09:00")
 * @param string $endTime   "HH:MM" (ie "14:30")
 * @param bool   $inclusive Whether to include the start and end time
 */
public function between(string $startTime, string $endTime, bool $inclusive = true)

/**
 * Skip when between given times.
 *
 * @param string $startTime "HH:MM" (ie "09:00")
 * @param string $endTime   "HH:MM" (ie "14:30")
 * @param bool   $inclusive Whether to include the start and end time
 */
public function unlessBetween(string $startTime, string $endTime, bool $inclusive = true)
```

### Example

Below is an example using most of the above hooks:

```php
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;

/* @var Schedule $schedule */
$schedule->addCommand('my:command')
    ->everyTenMinutes()
    ->timezone('UTC')
    ->filter(function () {
        throw new SkipTask('always skip task');
    })
    ->when('using boolean - will skip task', false)
    ->when('using callback - will skip task', function () { return false; })
    ->skip('using boolean - will skip task', true)
    ->skip('using callback - will skip task', function () { return true; })
    ->before(function () { /* runs before task runs */ })
    ->after(function () { /* runs after task runs */ })
    ->then(function () { /* runs after task runs */ })
    ->onSuccess(function () { /* runs after task runs if it was successful */ })
    ->onFailure(function () { /* runs after task runs if it failed */ })
    ->pingBefore('https://example.com/before-task-run')
    ->pingAfter('https://example.com/after-task-run')
    ->thenPing('https://example.com/after-task-run')
    ->pingOnSuccess('https://example.com/task-succeeded')
    ->pingOnFailure('https://example.com/task-failed')
    ->emailAfter()
    ->thenEmail()
    ->emailOnFailure()
    ->withoutOverlapping()
    ->onSingleServer()
    ->between(9, 5)
    ->unlessBetween(12, 13)
;
```

The following configuration is required for the above examples:

```yaml
# config/packages/zenstruck_schedule.yaml
zenstruck_schedule:
    single_server_handler: lock.default.factory # required to use "onSingleServer"
    email_handler: # required to use email hooks
        service: mailer
        default_from: webmaster@example.com
        default_to: webteam@example.com # required because not defining "to"
```
