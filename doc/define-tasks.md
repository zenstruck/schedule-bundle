# Defining Tasks

Tasks are defined by adding them to your schedule. See [Defining the Schedule](define-schedule.md)
to see where to add these.

## Task Types

### CommandTask

This task runs a Symfony console command.

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   command: my:command arg1 --option1 --option1=value
            frequency: 0 * * * *
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

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

*This task can only be defined in [PHP](define-schedule.md#schedulebuilder-service).*

```php
/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->addCallback(function () {
    // do something
    
    return 'task output';
});
```

### ProcessTask

This task executes shell commands.

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   command: bash:/bin/my-script # note the "bash:" prefix
            frequency: 0 * * * *
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

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

### CompoundTask

This is a special task that allows you to group other tasks together that share a
frequency, timezone and extensions.

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   command:
                - my:command arg --option
                - bash:/bin/my-script
            frequency: 0 * * * *
            timezone: UTC
            email_on_failure: ~

        -   command: # optionally key by the desired task description
                "run my command": my:command arg --option
                "run my bash bash script": bash:/bin/my-script
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

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

### NullTask

This task does nothing (is always successful) but allows you to register extensions
that run at this task's frequency. This can be useful for Cron health monitoring
tools like [Cronitor](https://cronitor.io/), [Laravel Envoyer](https://envoyer.io/)
and [Healthchecks](https://healthchecks.io/). You may want to ping their health
check endpoint every hour. Alternatively, you may want to receive an email once a
day to let you know your schedule is running as expected.

This task type *requires* a description.

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   command: null
            frequency: 0 * * * *
            description: my task # required for "null" tasks
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

```php
use Zenstruck\ScheduleBundle\Schedule\Task\NullTask;

/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->add(new NullTask('hourly health check'))
    ->hourly()
    ->pingOnSuccess('https://example.com/health-check')
;

$schedule->add(new NullTask('daily email'))
    ->daily()
    ->at(7)
    ->thenEmail('admin@example.com', 'The schedule is running!')
;
```

## Task Description

Optionally add a unique description to your task. If none is provided, tasks define a
default description based on their input. [NullTask](#nulltask) is the exception, a
description is required for this task type.

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   command: my:command
            frequency: 0 * * * *
            description: this describes my task
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

```php
/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->addCommand('my:command')
    ->description('this describes my task')
;
```

## Frequency

These are the options for defining how often your task runs:

### Cron Expression

A standard Cron expression. Check [crontab.guru](https://crontab.guru/) for
help.

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   command: my:command
            frequency: '0,30 9-17 * * 1-5' # every 30 minutes between 9am and 5pm on weekdays
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->cron('0,30 9-17 * * 1-5'); // every 30 minutes between 9am and 5pm on weekdays
```

### Fluent Expression Builder

If defining your schedule in [PHP](define-schedule.md#schedulebuilder-service), you
can build your cron expression using the fluent expression builder functions:

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task
    ->everyMinute()

    ->everyFiveMinutes()

    ->everyTenMinutes()

    ->everyFifteenMinutes()

    ->everyThirtyMinutes()

    ->hourly()

    ->hourlyAt(15) // 0-59

    ->daily()

    ->at('14:00')
    ->at(14) // can pass an integer as the hour and exclude the minutes

    ->dailyAt('14:30') // alias for ->at()

    ->twiceDaily()

    ->weekdays()

    ->weekends()

    ->weeklyOn(2, 4) // 0 = Sunday, 6 = Saturday

    ->mondays()

    ->tuesdays()

    ->wednesdays()

    ->thursdays()

    ->fridays()

    ->saturdays()

    ->sundays()

    ->weekly()

    ->monthly()

    ->monthlyOn(5)

    ->twiceMonthly()

    ->quarterly()

    ->yearly()
;
```

### Hashed Cron Expression

If you have many tasks scheduled at midnight (`0 0 * * *`) this could
create a very long running schedule right at this time. Tasks scheduled at
the same time are run synchronously. This may cause an issue if a task has
a memory leak.

This bundle extends the standard Cron expression syntax by adding an `H` (for *hash*)
symbol. `H` is replaced with a random value for the field. The selection is
deterministic based on the task's *description*. This means that while the value
is random, it is predictable. A task with the description `my task` and a defined
frequency of `H H * * *` will have a *calculated frequency* of `56 20 * * *` (every
day at 8:56pm). Changing the task's description will change it's *calculated
frequency*. If the task from the previous example's description is changed to
`another task`, it's *calculated frequency* would change to `24 12 * * *` (every
day at 12:24pm).

A hash range `H(x-y)` can also be used. For example, `H H(0-7) * * *` means daily,
some time between midnight and 7am. Using the `H` without a range creates a range
of any valid value for the field. `H H H H H` is short for
`H(0-59) H(0-23) H(1-28) H(1-12) H(0-6)`. *Note the day of month range is 1-28, this
is to account for February which has a minimum of 28 days.*

The following *hash* aliases are provided:

| Alias       | Converts to                                                            |
| ----------- | ---------------------------------------------------------------------- |
| `@hourly`   | `H * * * *` (at some minute every hour)                                |
| `@daily`    | `H H * * *` (at some time every day)                                   |
| `@midnight` | `H H(0-2) * * *` (at some time between midnight and 2:59am, every day) |
| `@weekly`   | `H H * * H` (at some time every week)                                  |
| `@monthly`  | `H H H * *` (at some time on some day, once per month)                 |
| `@annually` | `H H H H *` (at some time on some day, once per year)                  |
| `@yearly`   | `H H H H *` (at some time on some day, once per year)                  |

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   command: my:command
            description: my task
            frequency: 'H H * * H' # converts to "56 20 * * 0" (every Sunday @ 8:56pm)

        -   command: my:command
            description: another task
            frequency: 'H H(1-4) 1,15 * *' # converts to "24 1 1,15 * *" (1:24am on the first and fifteenth days of each month)

        -   command: my:command
            description: yet another task
            frequency: '@midnight' # converts to "52 1 * * *" (daily @ 1:52am)

        -   command: my:command
            description: yet another task 2 # note the different description calculates a different frequency
            frequency: '@midnight' # converts to "32 2 * * *" (daily @ 2:32am)
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task
    ->description('my task')
    ->cron('H H * * H') // converts to "56 20 * * 0" (every Sunday @ 8:56pm)
;

$task
    ->description('another task')
    ->cron('H H(1-4) 1,15 * *') // converts to "24 1 1,15 * *" (1:24am on the 1st and 15th of each month)
;

$task
    ->description('yet another task')
    ->cron('@midnight') // converts to "52 1 * * *" (daily @ 1:52am)
;

$task
    ->description('yet another task 2') // note the different description calculates a different frequency
    ->cron('@midnight') // converts to "32 2 * * *" (daily @ 2:32am)
;
```

## Timezone

You may optionally define the timezone to use when determining when to
run a task. If none is provided, it will use PHP's default timezone.

Alternatively, you can define the [timezone for all tasks](define-schedule.md#timezone)
(timezone defined on a task will take precedence).

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   command: my:command
            frequency: 0 * * * *
            timezone: UTC
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

```php
/* @var \Zenstruck\ScheduleBundle\Schedule\Task $task */

$task->timezone('UTC');

// alternatively, pass \DateTimeZone instance
$task->timezone(new \DateTimeZone('UTC'));
```

## Task Extensions

The following extensions are available when defining a task:

### Filters

*These extensions can only be configured in [PHP](define-schedule.md#schedulebuilder-service).*

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

*These extensions can only be configured in [PHP](define-schedule.md#schedulebuilder-service).*

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

This extension is useful for Cron health monitoring tools like
[Cronitor](https://cronitor.io/), [Laravel Envoyer](https://envoyer.io/) and
[Healthchecks](https://healthchecks.io/).

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   command: my:command
            frequency: 0 * * * *
            ping_before: https://example.com/before-task-run
            ping_after:
                url: https://example.com/after-task-runs
                method: POST
            ping_on_success: https://example.com/task-succeeded
            ping_on_failure: https://example.com/task-failed
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

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

This extension can be used to notify site administrators via email
that the task ran. Either just if it failed (`email_on_failure`) or
regardless of the result (`email_after`).

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   command: my:command
            frequency: 0 * * * *
            email_after: admin@example.com
            email_on_failure: ~ # default "to" address can be configured (see below)

        -   command: my:command
            frequency: 0 * * * *
            email_after:
                to: admin@example.com
                subject: my custom subject
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

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
            default_to: admin@example.com # optional (exclude if defined in code)
            default_from: webmaster@example.com # exclude only if a "global from" is defined for your application
            subject_prefix: "[Acme Inc]" # optional
    ```
   
3. Failed task emails have the subject `[Scheduled Task Failed] CommandTask: failed
   task description` (the subject can be configured). The email body has the following
   structure:

    ```
    failure description (ie exception message)
    
    ## Task Output
    
    Failed task's output (if any)
    
    ## Exception
    
    Failed task's exception stack trace (if any)
    ```

4. Successful task emails (if using `email_after`) have the subject
   `[Scheduled Task Succeeded] CommandTask: task description`. The email body
   has the following structure:

    ```
    Successful
    
    ## Task Output:
    
    Task's output (if any) 
    ```

### Prevent Overlap

This extension *locks* the task so it cannot run if it is still running from
a previous instance. If it is still running, the task is skipped.

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   command: my:command
            frequency: 0 * * * *
            without_overlapping: ~
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

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

*This extension can only be defined in [PHP](define-schedule.md#schedulebuilder-service).*

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

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   command: my:command1
            frequency: 0 * * * *
            between: 9-17 # only runs between 9am and 5pm (skips otherwise)

        -   command: my:command2
            frequency: 0 * * * *
            between: # only runs between 9:30pm and 6:15am (skips otherwise)
                start: 21:30
                end: 6:15

        -   command: my:command3
            frequency: 0 * * * *
            unless_between: 9-17 # skips if between 9am and 5pm

        -   command: my:command4
            frequency: 0 * * * *
            unless_between: # skips if between 9:30pm and 6:15am
                start: 21:30
                end: 6:15
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->between(9, 17); // only runs between 9am and 5pm (skips otherwise)
$task->between('21:30', '6:15'); // only runs between 9:30pm and 6:15am (skips otherwise)

$task->unlessBetween(9, 17); // skips if between 9am and 5pm
$task->unlessBetween('21:30', '6:15'); // skips if between 9:30pm and 6:15am
```
