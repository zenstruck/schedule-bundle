# Defining the Schedule

## Your Kernel

The simplest place to define your schedule is within your application's
`Kernel` by having it implement [`ScheduleBuilder`](../src/Schedule/ScheduleBuilder.php):

```php
// src/Kernel.php

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
// ...

class Kernel extends BaseKernel implements ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void
    {
        $schedule->addCommand('app:send-weekly-report --detailed')
            ->description('Send the weekly report to users.')
            ->sundays()
            ->at(1)
        ;

        $schedule->addCommand('app:send-hourly-report')
            ->hourly()
            ->between(9, 5)
        ;
    }

    // ...
}
```

## Self-Scheduling Commands

You can make your application's console commands schedule themselves:

1. Have your command implement [`SelfSchedulingCommand`](../src/Schedule/SelfSchedulingCommand.php):

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

2. *(optional)* Add the `schedule.self_scheduling_command` tag to the console 
service. This isn't necessary if you have autoconfigure enabled.

## ScheduleBuilder Service

You can define one or more services that implement
[`ScheduleBuilder`](../src/Schedule/ScheduleBuilder.php):

1. Create the service class:

    ```php
    // src/Schedule/MyScheduleBuilder.php
    
    use Zenstruck\ScheduleBundle\Schedule;
    use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
    
    class MyScheduleBuilder implements ScheduleBuilder
    {
        public function buildSchedule(Schedule $schedule): void
        {
            $schedule->addCommand('app:send-weekly-report --detailed')
                ->description('Send the weekly report to users.')
                ->sundays()
                ->at(1)
            ;
        }
    }
    ```

2. *(optional)* Add the `schedule.builder` tag to the service. This isn't necessary
if you have autoconfigure enabled.

## Schedule Hooks

The following hooks are available when defining your schedule:

### Filters

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

These hooks are useful for Cron health monitoring tools like
[Cronitor](https://cronitor.io/), [Laravel Envoyer](https://envoyer.io/) and
[Healthchecks](https://healthchecks.io/).

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

3. These extensions can *alternatively* be enabled in your configuration:

    ```yaml
    # config/packages/zenstruck_schedule.yaml

    zenstruck_schedule:
        schedule_extensions:
            ping_before:
                url: https://example.com/before-tasks-run
            ping_after:
                url: https://example.com/after-tasks-run
                method: POST
            ping_on_success:
                url: https://example.com/all-tasks-succeeded
            ping_on_failure:
                url: https://example.com/some-tasks-failed
    ```

### Email On Failure

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
            default_to: admin@hammfg.com # optional (exclude if defined in code/config)
            default_from: webmaster@hammfg.com # exclude only if a "global from" is defined for your application
    ```

3. This extension can *alternatively* be enabled in your configuration:

    ```yaml
    # config/packages/zenstruck_schedule.yaml

    zenstruck_schedule:
        schedule_extensions:
            email_on_failure:
               to: admin@example.com # optional if configured
               subject: my subject # optional, leave empty to use default
    ```

### Run on Single Server

This extension *locks* the schedule so it only runs on one server. The server
that starts running the schedule first wins. Other servers trying to run a *locked*
schedule will have their schedule skip. Be sure to configure this extension (see
below) with a **[remote store](https://symfony.com/doc/current/components/lock.html#remote-stores)**.
If you use a *local store* it will not be able to lock other servers.

```php
/* @var $schedule \Zenstruck\ScheduleBundle\Schedule */

$schedule->onSingleServer();
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

3. This extension can *alternatively* be enabled in your configuration:

    ```yaml
    # config/packages/zenstruck_schedule.yaml

    zenstruck_schedule:
        schedule_extensions:
            on_single_server: ~
    ```

### Limit to specific environment(s)

```php
/* @var $schedule \Zenstruck\ScheduleBundle\Schedule */

$schedule->environments('prod');
```

This extension can *alternatively* be enabled in your configuration:

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    schedule_extensions:
        environments: prod
```
