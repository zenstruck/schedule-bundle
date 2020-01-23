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
        -   task: my:command arg1 --option1 --option1=value
            frequency: '0 * * * *'
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
        -   task: bash:/bin/my-script # note the "bash:" prefix
            frequency: '0 * * * *'
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

```php
use Symfony\Component\Process\Process;

/* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

$schedule->addProcess('/bin/my-script');

// alternatively, add your own Process instance
$schedule->addProcess(
    Process::fromShellCommandline('/bin/my-script')
        ->setTimeout(10)
);
```

**Notes**:

1. This task requires `symfony/process`:

    ```console
    $ composer require symfony/process
    ```

2. The default *working directory* is the working directory of the current
   PHP process. If the task requires a specific working directory, it is
   best to set it explicitly.

   In PHP:

    ```php
    use Symfony\Component\Process\Process;

    /* @var \Zenstruck\ScheduleBundle\Schedule $schedule */

    $schedule->addProcess(
        Process::fromShellCommandline('bin/my-script')
            ->setWorkingDirectory('/www/my-project')
    );
    ```

   In Configuration:

    ```yaml
    # config/packages/zenstruck_schedule.yaml
    
    zenstruck_schedule:
        tasks:
            -   task: 'bash:cd %kernel.project_dir% && bin/my-script'
                frequency: '0 * * * *'
    ```

### CompoundTask

This is a special task that allows you to group other tasks together that share a
frequency, timezone and extensions. When due, grouped tasks are run in the order
they are defined.

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   task:
                - my:command arg --option
                - bash:/bin/my-script
            frequency: '0 * * * *'
            timezone: UTC
            email_on_failure: ~

        -   task: # optionally key by the desired task description
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
        -   task: null
            frequency: '0 * * * *'
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

## Task Services

If you have a [custom task](extending.md#custom-tasks), you can define it as a service
and add via the [bundle configuration](define-schedule.md#bundle-configuration):

```yaml
# config/services.yaml

services:
    my_task: App\Schedule\Task\MyTask

# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   task: '@my_task'
            frequency: '0 * * * *'
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
        -   task: my:command
            frequency: '0 * * * *'
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
help. The extended expression syntax may be used (`@hourly`, `@daily`, `@weekly`,
`@monthly`, `@yearly`, `@annually`).

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   task: my:command
            frequency: '0,30 9-17 * * 1-5' # every 30 minutes between 9am and 5pm on weekdays

        -   task: my:command
            frequency: '@daily' # daily @ midnight
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->cron('0,30 9-17 * * 1-5'); // every 30 minutes between 9am and 5pm on weekdays

$task->cron('@daily');  //daily @ midnight
```

### Fluent Expression Builder

If defining your schedule in [PHP](define-schedule.md#schedulebuilder-service), you
can build your cron expression using the fluent expression builder functions. The
default frequency for a task is every minute (expression: `* * * * *`).

The following methods reset the expression. Only one should be used and before any
*field adjustments*:

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->everyMinute();
$task->everyFiveMinutes();
$task->everyTenMinutes();
$task->everyFifteenMinutes();
$task->everyTwentyMinutes();
$task->everyThirtyMinutes();

$task->hourly(); // every hour on the hour
$task->hourlyAt(3); // every hour, 2 minutes past the hour
$task->hourlyAt(3, 33, '48-50'); // every hour, 3, 33, 48, 49 and 50 minutes past the hour

$task->daily(); // every day @ midnight
$task->dailyOn(3); // every day @ 3am
$task->dailyOn(3, 6, '17-18'); // every day @ 3am, 6am, 5pm and 6pm
$task->dailyBetween(9, 17); // every day, hourly between 9am and 5pm
$task->twiceDaily(); // every day @ 1am and 1pm
$task->twiceDaily(9, 17); // every day @ 9am and 5pm
$task->dailyAt(3); // daily @ 3am
$task->dailyAt('17:45'); // daily @ 5:45pm

$task->weekly(); // every Sunday @ midnight
$task->weeklyOn(1); // every Monday @ midnight
$task->weeklyOn(1, 3, '5-6'); // every Monday, Wednesday, Friday and Saturday @ midnight

$task->monthly(); // every 1st of the month @ midnight
$task->monthlyOn(3); // every 3rd of the month @ midnight
$task->monthlyOn(3, 6, '8-10'); // every 3rd, 6th, 8th, 9th and 10th of the month @ midnight
$task->twiceMonthly(); // every 1st and 16th of the month @ midnight
$task->twiceMonthly(3, 21); // every 3rd and 21st of the month @ midnight

$task->yearly(); // every January 1st @ midnight

$task->quarterly(); // every January 1st, April 1st, July 1st and October 1st @ midnight
```

The following methods adjust individual fields (with the exception if `->at()` which
optionally adjusts the hour and minute):

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->minutes(3); // sets the "minutes" field to "3"
$task->minutes(3, 6, '45-50'); // sets the "minutes" field to "3,6,45-50"

$task->hours(3); // sets the "hours" field to "3"
$task->hours(3, 6, '12-14'); // sets the "hours" field to "3,6,12-14"

$task->at(4); // sets the "hours" field to "4"
$task->at('4:45'); // sets the "hours" field to "4" and the "minutes" field to "45"

$task->daysOfMonth(3); // sets the "days of month" field to "3"
$task->daysOfMonth(3, 6, '18-20'); // sets the "days of month" field to "3,6,18-20"

$task->months(2); // sets the "months" field to "2" (February)
$task->months(2, 3, '10-12'); // sets the "months" field to "2,3,10-12" (February, March, October, November and December)

$task->daysOfWeek(1); // sets the "days of week" field to "1" (Monday)
$task->daysOfWeek(1, '3-5'); // sets the "days of week" field to "1,3-5" (Monday, Wednesday, Thursday, Friday)
$task->weekdays(); // sets the "days of week" field to "1-5" (Monday-Friday)
$task->weekends(); // sets the "days of week" field to "6,0" (Saturday,Sunday)
$task->mondays(); // sets the "days of week" field to "1" (Monday)
$task->tuesdays(); // sets the "days of week" field to "2" (Tuesday)
$task->wednesdays(); // sets the "days of week" field to "3" (Wednesday)
$task->thursdays(); // sets the "days of week" field to "4" (Thursday)
$task->fridays(); // sets the "days of week" field to "5" (Friday)
$task->saturdays(); // sets the "days of week" field to "6" (Saturday)
$task->sundays(); // sets the "days of week" field to "0" (Sunday)
```

### Hashed Cron Expression

If you have many tasks scheduled at midnight (`0 0 * * *`) this could
create a very long running schedule right at this time. Tasks scheduled at
the same time are run synchronously. This may cause an issue if a task has
a memory leak.

This bundle extends the standard Cron expression syntax by adding a `#` (for *hash*)
symbol. `#` is replaced with a random value for the field. The value is
deterministic based on the task's *description*. This means that while the value
is random, it is predictable and consistent. A task with the description `my task`
and a defined frequency of `# # * * *` will have a *calculated frequency* of
`56 20 * * *` (every day at 8:56pm). Changing the task's description will change
it's *calculated frequency*. If the task from the previous example's description
is changed to `another task`, it's *calculated frequency* would change to
`24 12 * * *` (every day at 12:24pm).

A hash range `#(x-y)` can also be used. For example, `# #(0-7) * * *` means daily,
some time between midnight and 7am. Using the `#` without a range creates a range
of any valid value for the field. `# # # # #` is short for
`#(0-59) #(0-23) #(1-28) #(1-12) #(0-6)`. *Note the day of month range is 1-28, this
is to account for February which has a minimum of 28 days.*

The following *hash* aliases are provided:

| Alias       | Converts to                                                            |
| ----------- | ---------------------------------------------------------------------- |
| `#hourly`   | `# * * * *` (at some minute every hour)                                |
| `#daily`    | `# # * * *` (at some time every day)                                   |
| `#midnight` | `# #(0-2) * * *` (at some time between midnight and 2:59am, every day) |
| `#weekly`   | `# # * * #` (at some time every week)                                  |
| `#monthly`  | `# # # * *` (at some time on some day, once per month)                 |
| `#annually` | `# # # # *` (at some time on some day, once per year)                  |
| `#yearly`   | `# # # # *` (at some time on some day, once per year)                  |

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   task: my:command
            description: my task
            frequency: '# # * * #' # converts to "56 20 * * 0" (every Sunday @ 8:56pm)

        -   task: my:command
            description: another task
            frequency: '# #(1-4) 1,15 * *' # converts to "24 1 1,15 * *" (1:24am on the first and fifteenth days of each month)

        -   task: my:command
            description: yet another task
            frequency: '#midnight' # converts to "52 1 * * *" (daily @ 1:52am)

        -   task: my:command
            description: yet another task 2 # note the different description calculates a different frequency
            frequency: '#midnight' # converts to "32 2 * * *" (daily @ 2:32am)
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task
    ->description('my task')
    ->cron('# # * * #') // converts to "56 20 * * 0" (every Sunday @ 8:56pm)
;

$task
    ->description('another task')
    ->cron('# #(1-4) 1,15 * *') // converts to "24 1 1,15 * *" (1:24am on the 1st and 15th of each month)
;

$task
    ->description('yet another task')
    ->cron('#midnight') // converts to "52 1 * * *" (daily @ 1:52am)
;

$task
    ->description('yet another task 2') // note the different description calculates a different frequency
    ->cron('#midnight') // converts to "32 2 * * *" (daily @ 2:32am)
;
```

## Task ID

Each task has an ID that is a hash of the task type,
[frequency expression](#frequency) and [description](#task-description).
The [`schedule:list --detail`](cli-commands.md#schedulelist) command
shows each task's ID. These ID's should be unique but it is not enforced.
If you have multiple tasks of the same type, frequency and description, the
their ID's will be duplicated. Running `schedule:list` will alert you if
this is the case. ID's can be used to "[force run](run-schedule.md#force-run)"
tasks.

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
        -   task: my:command
            frequency: '0 * * * *'
            timezone: UTC
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

```php
/* @var \Zenstruck\ScheduleBundle\Schedule\Task $task */

$task->timezone('UTC');

// alternatively, pass a \DateTimeZone instance
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
        -   task: my:command
            frequency: '0 * * * *'
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
        -   task: my:command
            frequency: '0 * * * *'
            email_after: admin@example.com
            email_on_failure: ~ # default "to" address can be configured (see below)

        -   task: my:command
            frequency: '0 * * * *'
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
    Result: "failure description (ie exception message)"

    Task ID: <task ID>

    ## Task Output

    Failed task's output (if any)

    ## Exception

    Failed task's exception stack trace (if any)
    ```

4. Successful task emails (if using `email_after`) have the subject
   `[Scheduled Task Succeeded] CommandTask: task description`. The email body
   has the following structure:

    ```
    Result: "Successful"

    Task ID: <task ID>

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
        -   task: my:command
            frequency: '0 * * * *'
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

*only_between* skips the task if run outside of the given range. *unless_between*
skips the task if run inside the the given range.

**Define in [Configuration](define-schedule.md#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    tasks:
        -   task: my:command1
            frequency: '0 * * * *'
            only_between: 9-17 # only runs between 9am and 5pm (skips otherwise)

        -   task: my:command2
            frequency: '0 * * * *'
            only_between: # only runs between 9:30pm and 6:15am (skips otherwise)
                start: 21:30
                end: 6:15

        -   task: my:command3
            frequency: '0 * * * *'
            unless_between: 9-17 # skips if between 9am and 5pm

        -   task: my:command4
            frequency: '0 * * * *'
            unless_between: # skips if between 9:30pm and 6:15am
                start: 21:30
                end: 6:15
```

**Define in [PHP](define-schedule.md#schedulebuilder-service):**

```php
/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->onlyBetween(9, 17); // only runs between 9am and 5pm (skips otherwise)
$task->onlyBetween('21:30', '6:15'); // only runs between 9:30pm and 6:15am (skips otherwise)

$task->unlessBetween(9, 17); // skips if between 9am and 5pm
$task->unlessBetween('21:30', '6:15'); // skips if between 9:30pm and 6:15am
```
