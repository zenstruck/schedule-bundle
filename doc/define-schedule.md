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

2. _(optional)_ Add the `schedule.self_scheduling_command` tag to the console 
service. This isn't necessary if you have autoconfigure enabled.

## ScheduleBuilder Service

Alternatively, you can define one or more services that implement
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

2. _(optional)_ Add the `schedule.builder` tag to the service. This isn't necessary
if you have autoconfigure enabled.

## Schedule Hooks

The following hooks are available when defining your schedule (on the `$schedule` object):

### Filters

```php
/**
 * Prevent schedule from running if callback throws \Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule.
 *
 * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent
 */
public function filter(callable $callback)

/**
 * Only run schedule if true.
 *
 * @param bool|callable $callback bool: skip if false, callable: skip if return value is false
 *                                callable receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent
 */
public function when(string $description, $callback)

/**
 * Skip schedule if true.
 *
 * @param bool|callable $callback bool: skip if true, callable: skip if return value is true
 *                                callable receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent
 */
public function skip(string $description, $callback)
```

### Callbacks

```php
/**
 * Execute callback before tasks run (even if no tasks are due).
 *
 * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent
 */
public function before(callable $callback)

/**
 * Execute callback after tasks run (even if no tasks ran).
 *
 * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\AfterScheduleEvent
 */
public function after(callable $callback)

/**
 * Alias for after().
 */
public function then(callable $callback)

/**
 * Execute callback after tasks run if all tasks succeeded
 *  - even if no tasks ran
 *  - skipped tasks are considered successful.
 *
 * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\AfterScheduleEvent
 */
public function onSuccess(callable $callback)

/**
 * Execute callback after tasks run if one or more tasks failed
 *  - even if no tasks ran
 *  - skipped tasks are considered successful.
 *
 * @param callable $callback Receives an instance of \Zenstruck\ScheduleBundle\Event\AfterScheduleEvent
 */
public function onFailure(callable $callback)
```

### Ping Webhook

```php
/**
 * Ping a webhook before any tasks run (even if none are due).
 * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
 *
 * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
 */
public function pingBefore(string $url, string $method = 'GET', array $options = [])

/**
 * Ping a webhook after tasks ran (even if none ran).
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
 * Ping a webhook after tasks run if all tasks succeeded (skipped tasks are considered successful).
 * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
 *
 * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
 */
public function pingOnSuccess(string $url, string $method = 'GET', array $options = [])

/**
 * Ping a webhook after tasks run if one or more tasks failed.
 * If you want to control the HttpClientInterface used, configure `zenstruck_schedule.ping_handler`.
 *
 * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
 */
public function pingOnFailure(string $url, string $method = 'GET', array $options = [])
```

### Email On Failure

```php
/**
 * Email failed task detail after tasks run if one or more tasks failed.
 * Be sure to configure `zenstruck_schedule.email_handler`.
 *
 * @param string|string[] $to       Email address(es)
 * @param callable|null   $callback Add your own headers etc
 *                                  Receives an instance of \Symfony\Component\Mime\Email
 */
public function emailOnFailure($to = null, string $subject = null, callable $callback = null)
```

### Run on Single Server

```php
/**
 * Restrict running of schedule to a single server.
 * Be sure to configure `zenstruck_schedule.single_server_handler`.
 *
 * @param int $ttl Maximum expected lock duration in seconds
 */
public function onSingleServer(int $ttl = 3600)
```

### Limit to specific environment(s)

```php
/**
 * Define the application environment(s) you wish to run the schedule in. Trying to
 * run in another environment will skip the schedule.
 */
public function inEnvironment(string ...$environment)
```

### Example

Here is an example using all the above hooks:

```php
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;

/* @var Schedule $schedule */
$schedule
    ->filter(function () {
        throw new SkipSchedule('always skip schedule');
    })
    ->when('using boolean - will skip schedule', false)
    ->when('using callback - will skip schedule', function () { return false; })
    ->skip('using boolean - will skip schedule', true)
    ->skip('using callback - will skip schedule', function () { return true; })
    ->before(function () { /* runs before any tasks run */ })
    ->after(function () { /* runs after all due tasks run */ })
    ->then(function () { /* runs after all due tasks run */ })
    ->onSuccess(function () { /* runs after all due tasks run if no tasks failed */ })
    ->onFailure(function () { /* runs after all due tasks run if 1 or more tasks failed */ })
    ->pingBefore('https://example.com/before-any-tasks-run')
    ->pingAfter('https://example.com/after-due-tasks-run')
    ->thenPing('https://example.com/after-due-tasks-run')
    ->pingOnSuccess('https://example.com/no-tasks-failed')
    ->pingOnFailure('https://example.com/some-tasks-failed')
    ->emailOnFailure()
    ->onSingleServer()
    ->environments('prod')
;
```

Alternatively, some of the above hooks can be configured:

```yaml
# config/packages/zenstruck_schedule.yaml
zenstruck_schedule:
    single_server_handler: lock.default.factory # required to use "onSingleServer"
    email_handler:
        service: mailer
        default_from: webmaster@example.com
        default_to: webteam@example.com
    schedule_extensions:
        environments: prod
        on_single_server: ~
        email_on_failure: ~
        ping_before:
            url: https://example.com/before-any-tasks-run
        ping_after:
            url: https://example.com/after-due-tasks-run
        ping_on_success:
            url: https://example.com/no-tasks-failed
        ping_on_failure:
            url: https://example.com/some-tasks-failed
```
