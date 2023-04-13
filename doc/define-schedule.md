# Defining the Schedule

## ScheduleBuilder Service

You can define one or more services that implement
[`ScheduleBuilder`](../src/Schedule/ScheduleBuilder.php):

```php
// src/Schedule/MyScheduleBuilder.php

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

class MyScheduleBuilder implements ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void
    {
        $schedule->timezone('UTC');

        $schedule->addCommand('app:send-weekly-report --detailed')
            ->description('Send the weekly report to users.')
            ->sundays()
            ->at(1)
        ;
    }
}
```

**NOTE:** If *autoconfiguration* is not enabled, add the `schedule.builder` tag to
the service.

## Your Kernel

Have your application's `Kernel` implement
[`ScheduleBuilder`](../src/Schedule/ScheduleBuilder.php):

```php
// src/Kernel.php

use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

class Kernel extends BaseKernel implements ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void
    {
        $schedule->timezone('UTC');

        $schedule->addCommand('app:send-weekly-report --detailed')
            ->description('Send the weekly report to users.')
            ->sundays()
            ->at(1)
        ;

        $schedule->addCommand('app:send-hourly-report')
            ->hourly()
            ->onlyBetween(9, 17) // between 9am and 5pm
        ;
    }

    // ...
}
```

## Bundle Configuration

Most [tasks](define-tasks.md#task-types), [task extensions](define-tasks.md#task-extensions)
and [schedule extensions](#schedule-extensions) can be configured:

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    timezone: UTC
    schedule_extensions:
        ping_on_success: https://example.com/schedule-success

    tasks:
        -   task: app:send-weekly-report --detailed
            frequency: '0 1 * * 0' # sundays @ 1am

        -   task: app:send-hourly-report
            frequency: '0 0 * * 1-5' # hourly on weekdays
            only_between: 9-17 # only run between 9am and 5pm
            unless_between: 11-13 # except at lunch
```

## `AsScheduledTask` Attribute

_**NOTE:** PHP 8+ and Symfony 5.4+ required to use this feature._

You can mark [invokable services](#invokable-asscheduledtask-services) and
[console commands](#asscheduledtask-console-commands) with the
`Zenstruck\ScheduleBundle\Attribute\AsScheduledTask` attribute to _self-schedule_ them.

### Invokable `AsScheduledTask` Services

Services can be marked with `AsScheduledTask` to be scheduled (as a
[`CallbackTask`](define-tasks.md#callbacktask)). These services must be _callable_
(implement `__invoke()`) or have a custom method configured. The method must be
public and have no required parameters.

```php
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;

#[AsScheduledTask('#daily')]
#[AsScheduledTask('#weekly')] // can be scheduled multiple times
#[AsScheduledTask('#monthly', description: 'some description')] // optionally set a description
#[AsScheduledTask('#daily', method: 'someOtherMethod')] // use a different method
class MyService
{
    public function __invoke(): void
    {
    }

    public function someOtherMethod(): void
    {
    }
}
```

### `AsScheduledTask` Console Commands

Console commands can be marked with `AsScheduledTask` to _self-schedule_ them.

**NOTE:** Use [Self-Scheduling Commands](#self-scheduling-commands) if you require more
fine-grained options.

```php
use Symfony\Component\Console\Command;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;

#[AsScheduledTask('#daily')]
#[AsScheduledTask('#weekly')] // can be scheduled multiple times
#[AsScheduledTask('#monthly', description: 'some description')] // optionally set a description
#[AsScheduledTask('#daily', arguments: '--no-interaction --verbose')] // optionally set arguments
class MyCommand extends Command
{
    // ...
}
```

## Self-Scheduling Commands

You can make your application's console commands schedule themselves. Have your command
implement [`SelfSchedulingCommand`](../src/Schedule/SelfSchedulingCommand.php):

**NOTE:** If using PHP 8+ and Symfony 5.4+, see [`AsScheduledTask` Console Commands](#asscheduledtask-console-commands)
as a possible alternative.

```php
// src/Command/WeeklyReportCommand.php

use Symfony\Component\Console\Command\Command;
use Zenstruck\ScheduleBundle\Schedule\SelfSchedulingCommand;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;

class WeeklyReportCommand extends Command implements SelfSchedulingCommand
{
    // ...

    public function schedule(CommandTask $task) : void
    {
        $task
            ->arguments('--detailed')
            ->sundays()
            ->at(1)
        ;
    }
}
```

**NOTE:** If *autoconfiguration* is not enabled, add the `schedule.self_scheduling_command`
tag to the service.

## Timezone

You may optionally define the *schedule* timezone for all tasks to use. If none is provided,
it will use PHP's default timezone. [Tasks can override](define-tasks.md#timezone)
the *schedule* timezone.

**Define in [PHP](#schedulebuilder-service):**

```php
/* @var $schedule \Zenstruck\ScheduleBundle\Schedule */

$schedule->timezone('America/New_York');
```

**Define in [Configuration](#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    timezone: America/New_York
```

## Schedule Extensions

The following extensions are available when defining your schedule:

### Filters

*These extensions can only be defined in [PHP](#schedulebuilder-service).*

```php
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;

/* @var $schedule \Zenstruck\ScheduleBundle\Schedule */

$schedule->filter(function () {
    if (some_condition()) {
        throw new SkipSchedule('skipped because...');
    }
});

$schedule->when('skipped because...', some_condition()); // only runs if true
$schedule->when('skipped because...', function () { // only runs if return value is true
    return some_condition();
});

$schedule->skip('skipped because...', some_condition()); // skips if true
$schedule->skip('skipped because...', function () { // skips if return value is true
    return some_condition();
});
```

### Callbacks

*These extensions can only be defined in [PHP](#schedulebuilder-service).*

```php
/* @var $schedule \Zenstruck\ScheduleBundle\Schedule */

$schedule->before(function () {
    // executes before tasks run (even if none are due)
});

$schedule->after(function () {
    // executes after tasks run (even if none ran)
});

$schedule->then(function () {
    // alias for ->after()
});

$schedule->onSuccess(function () {
    // executes if all tasks succeeded
});

$schedule->onFailure(function () {
    // executes if 1 or more tasks failed
});
```

### Ping Webhook

This extension is useful for Cron health monitoring tools like [Oh Dear](https://ohdear.app/),
[Cronitor](https://cronitor.io/) and [Healthchecks](https://healthchecks.io/).

**Define in [PHP](#schedulebuilder-service):**

```php
/* @var $schedule \Zenstruck\ScheduleBundle\Schedule */

$schedule->pingBefore('https://example.com/before-tasks-run');

// even if none ran
$schedule->pingAfter('https://example.com/after-tasks-run', 'POST');

// alias for ->pingAfter()
$schedule->thenPing('https://example.com/after-tasks-run');

// even if none ran, skipped tasks are considered successful
$schedule->pingOnSuccess('https://example.com/all-tasks-succeeded');

$schedule->pingOnFailure('https://example.com/some-tasks-failed');
```

**Define in [Configuration](#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    schedule_extensions:
        ping_before: https://example.com/before-tasks-run
        ping_after:
            url: https://example.com/after-tasks-run
            method: POST
        ping_on_success:
            url: https://example.com/all-tasks-succeeded
        ping_on_failure:
            url: https://example.com/some-tasks-failed
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
        http_client: my_http_client
    ```

### Email On Failure

This extension can be used to notify site administrators via email
when tasks fail.

**Define in [PHP](#schedulebuilder-service):**

```php
/* @var $schedule \Zenstruck\ScheduleBundle\Schedule */

$schedule->emailOnFailure('admin@example.com');

// default "to" address can be configured (see below)
$schedule->emailOnFailure();

// add custom headers/etc
$schedule->emailOnFailure('admin@example.com', 'my email subject', function (\Symfony\Component\Mime\Email $email) {
    $email->addCc('sales@example.com');
    $email->getHeaders()->addTextHeader('X-TRACKING', 'enabled');
});
```

**Define in [Configuration](#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    schedule_extensions:
        email_on_failure:
           to: admin@example.com # optional if configured
           subject: my subject # optional, leave empty to use default
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
        mailer:
            service: mailer # required
            default_to: admin@example.com # optional (exclude if defined in code/config)
            default_from: webmaster@example.com # exclude only if a "global from" is defined for your application
            subject_prefix: "[Acme Inc]" # optional
    ```

3. The email has the subject `[Schedule Failure] 2 tasks failed`
   (assuming 2 tasks failed, the subject can be configured). The email body
   has the following structure:

    ```
    2 tasks failed

    # (Failure 1/2) CommandTask: failed task 1 description

    Result: "failure description (ie exception message)"

    Task ID: <task ID>

    ## Task Output

    Failed task's output (if any)

    ## Exception

    Failed task's exception stack trace (if any)

    ---

    # (Failure 2/2) CommandTask: failed task 2 description

    Result: "failure description (ie exception message)"

    Task ID: <task ID>

    ## Task Output

    Failed task's output (if any)

    ## Exception

    Failed task's exception stack trace (if any)
    ```

### Notify On Failure

This extension can be used to notify site administrators via any notification
when tasks fail.

**Define in [PHP](#schedulebuilder-service):**

```php
/* @var $schedule \Zenstruck\ScheduleBundle\Schedule */

$schedule->notifyOnFailure(['chat/slack', 'sms', 'email'], 'admin@example.com', '123456789');

// default channel can be configured (see below)
$schedule->notifyOnFailure();

// customise the notification
$schedule->notifyOnFailure('chat/slack', null, null, null, function (\Symfony\Component\Notifier\Notification\Notification $notification) {
    $notification->emoji('user');
});
```

**Define in [Configuration](#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    schedule_extensions:
        notify_on_failure:
           channel: chat/slack; # optional if configured
           subject: my subject # optional, leave empty to use default
```

**Notes:**

1. This extension **requires** `symfony/notifier`:

    ```console
    $ composer require symfony/notifier
    ```

2. This extension **requires** configuration:

    ```yaml
    # config/packages/zenstruck_schedule.yaml

    zenstruck_schedule:
        notifier:
            service: notifier # required
            default_channel: chat/slack # optional (exclude if defined in code/config)
            default_email: webmaster@example.com # optional
            default_phone: 1234567890 # optional
            subject_prefix: "[Acme Inc]" # optional
    ```

3. The notification has the subject `[Schedule Failure] 2 tasks failed`
   (assuming 2 tasks failed, the subject can be configured). The content
   has the following structure:

    ```
    2 tasks failed

    # (Failure 1/2) CommandTask: failed task 1 description

    Result: "failure description (ie exception message)"

    Task ID: <task ID>

    ## Task Output

    Failed task's output (if any)

    ## Exception

    Failed task's exception stack trace (if any)

    ---

    # (Failure 2/2) CommandTask: failed task 2 description

    Result: "failure description (ie exception message)"

    Task ID: <task ID>

    ## Task Output

    Failed task's output (if any)

    ## Exception

    Failed task's exception stack trace (if any)
    ```

### Run on Single Server

This extension *locks* the schedule so it only runs on one server. The server
that starts running the schedule first wins. Other servers trying to run a *locked*
schedule will have their schedule skip. Be sure to configure this extension (see
below) with a **[remote store](https://symfony.com/doc/current/components/lock.html#remote-stores)**.
If you use a *local store* it will not be able to lock other servers.

**Define in [PHP](#schedulebuilder-service):**

```php
/* @var $schedule \Zenstruck\ScheduleBundle\Schedule */

$schedule->onSingleServer();
```

**Define in [Configuration](#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    schedule_extensions:
        on_single_server: ~
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
        single_server_lock_factory: lock.default.factory # Be sure to use a "remote store" (https://symfony.com/doc/current/components/lock.html#remote-stores)
    ```
    If you want to use the default lock service configured in your `config/packages/lock.yaml` use `lock.default.factory`

### Limit to specific environment(s)

**Define in [PHP](#schedulebuilder-service):**

```php
/* @var $schedule \Zenstruck\ScheduleBundle\Schedule */

$schedule->environments('prod');
```

**Define in [Configuration](#bundle-configuration):**

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    schedule_extensions:
        environments: prod
```
