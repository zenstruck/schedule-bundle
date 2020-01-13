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
task *output*.

```php
/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->addCallback(function () {
    // do something
    
    return 'task output';
});
```

### ProcessTask

This task executes shell commands.

```php
/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->addProcess('/bin/my-script');

// alternatively, add your own Process instance
$process = new \Symfony\Component\Process\Process(['/bin/my-script']);
$process->setWorkingDirectory('/home/user');
$process->setTimeout(10);

$schedule->addProcess($process);
```

**Note:** this task requires `symfony/process`:

```console
$ composer require symfony/process
```

### NullTask

This task does nothing (is always successful) but allows you to register hooks
that run at this task's frequency. This can be useful for Cron health monitoring
tools like [Cronitor](https://cronitor.io/), [Laravel Envoyer](https://envoyer.io/)
and [Healthchecks](https://healthchecks.io/). You may want to ping their health
check endpoint every hour. Alternatively, you may want to receive an email once a
day to let you know your schedule is running as expected.

```php
/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->addNull('hourly health check')
    ->hourly()
    ->pingOnSuccess('https://example.com/health-check')
;

$schedule->addNull('daily email')
    ->daily()
    ->at(7)
    ->thenEmail('admin@example.com', 'The schedule is running!')
;
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
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->everyMinute();

$task->everyFiveMinutes();

$task->everyTenMinutes();

$task->everyFifteenMinutes();

$task->everyThirtyMinutes();

$task->hourly();

$task->hourlyAt(15); // 0-59

$task->daily();

$task->at('14:00');
$task->at(14); // can pass an integer as the hour and exclude the minutes

$task->dailyAt('14:30'); // alias for ->at()

$task->twiceDaily();

$task->weekdays();

$task->weekends();

$task->days(2, 4); // 0 = Sunday, 6 = Saturday

$task->mondays();

$task->tuesdays();

$task->wednesdays();

$task->thursdays();

$task->fridays();

$task->saturdays();

$task->sundays();

$task->weekly();

$task->monthly();

$task->monthlyOn(5);

$task->twiceMonthly();

$task->quarterly();

$task->yearly();

$task->cron('15 3 * * 1,4');
```

## Timezone

You may optionally define the timezone to use when determining when to
run a task. If none is provided, it will use PHP's default timezone.

```php
/* @var \Zenstruck\ScheduleBundle\Schedule\Task $task */

$task->timezone('UTC');

// alternatively, pass \DateTimeZone instance
$task->timezone(new \DateTimeZone('UTC'));
```

Alternatively, you can configure the timezone for all tasks (timezone defined
on a task will take precedence):

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    timezone: UTC
```

## Task Hooks

The following hooks are available when defining a task:

### Filters

```php
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;

/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->filter(function () {
    if (some_condition()) {
        throw new SkipTask('skipped because...');
    }
});

$task->when('skipped because...', some_condition()); // only runs if true
$task->when('skipped because...', function () { // only runs if return value is true
    return some_condition();
});

$task->skip('skipped because...', some_condition()); // skips if true
$task->skip('skipped because...', function () { // skips if return value is true
    return some_condition();
});
```

### Callbacks

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->before(function () {
    // executes before task runs
});

$task->after(function () {
    // executes after task runs
});

$task->then(function () {
    // alias for ->after()
});

$task->onSuccess(function () {
    // executes if task succeeded
});

$task->onFailure(function () {
    // executes if task failed
});
```

### Ping Webhook

These hooks are useful for Cron health monitoring tools like
[Cronitor](https://cronitor.io/), [Laravel Envoyer](https://envoyer.io/) and
[Healthchecks](https://healthchecks.io/).

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->pingBefore('https://example.com/before-task-run');

$task->pingAfter('https://example.com/after-task-runs', 'POST');

// alias for ->pingAfter()
$task->thenPing('https://example.com/after-task-runs');

$task->pingOnSuccess('https://example.com/task-succeeded');

$task->pingOnFailure('https://example.com/task-failed');
```

**Notes**:

1. This extension **requires** `symfony/http-client`:

    ```console
    $ composer require symfony/http-client
    ```

2. *Optionally* customize the `HttpClient` service in your configuration:

    ```yaml
    # config/packages/zenstruck_schedule.yaml

    zenstruck_schedule:
        ping_handler: my_http_client
    ```


### Email Output

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->emailAfter('admin@example.com');

$task->thenEmail('admin@example.com'); // alias for ->emailAfter()

$task->emailOnFailure('admin@example.com');

// default "to" address can be configured (see below)
$task->emailAfter();
$task->emailOnFailure();

// add custom headers/etc
$task->emailAfter('admin@example.com', 'my email subject', function (Symfony\Component\Mime\Email $email) {
    $email->addCc('sales@example.com');
    $email->getHeaders()->addTextHeader('X-TRACKING', 'enabled');
});
$task->emailOnFailure('admin@example.com', 'my email subject', function (Symfony\Component\Mime\Email $email) {
    $email->addCc('sales@example.com');
    $email->getHeaders()->addTextHeader('X-TRACKING', 'enabled');
});
```

**Notes:**

1. This extension **requires** `symfony/mailer`:

    ```console
    $ composer require symfony/mailer
    ```

2. This extension **requires** configuration:

    ```yaml
    # config/packages/zenstruck_schedule.yaml

    zenstruck_schedule:
        email_handler:
            service: mailer # required
            default_to: admin@hammfg.com # optional (exclude if defined in code)
            default_from: webmaster@hammfg.com # exclude only if a "global from" is defined for your application
    ```

### Prevent Overlap

This extension *locks* the task so it cannot run if it is still running from
a previous instance. If it is still running, the task is skipped.

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->withoutOverlapping();
```

**Notes:**

1. This extension **requires** `symfony/lock`:

    ```console
    $ composer require symfony/lock
    ```
   
2. *Optionally* customize the `LockFactory` service in your configuration:

    ```yaml
    # config/packages/zenstruck_schedule.yaml

    zenstruck_schedule:
        without_overlapping_handler: my_lock_factory
    ```

### Run on Single Server

This extension *locks* the task so it only runs on one server. The server
that starts running the task first wins. Other servers trying to run a *locked*
task will have their task skip. Be sure to configure this extension (see
below) with a **[remote store](https://symfony.com/doc/current/components/lock.html#remote-stores)**.
If you use a *local store* it will not be able to lock other servers.

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->onSingleServer();
```

**Notes:**

1. This extension **requires** `symfony/lock`:

    ```console
    $ composer require symfony/lock
    ```

2. This extension **requires** configuration:

    ```yaml
    # config/packages/zenstruck_schedule.yaml

    zenstruck_schedule:
        single_server_handler: my_lock_factory_service # Be sure to use a "remote store" (https://symfony.com/doc/current/components/lock.html#remote-stores)
    ```

### Between

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->between(9, 17); // only runs between 9am and 5pm (skips otherwise)
$task->between('21:30', '6:15'); // only runs between 9:30pm and 6:15am (skips otherwise)

$task->unlessBetween(9, 17); // skips if between 9am and 5pm
$task->unlessBetween('21:30', '6:15'); // skips if between 9:30pm and 6:15am
```
