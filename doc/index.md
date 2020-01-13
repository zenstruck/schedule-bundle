# The ScheduleBundle

Schedule Cron jobs (Symfony commands/callbacks/bash scripts) within your Symfony
application. Most applications have jobs that need to run at specific intervals.
This bundle enables you to define these jobs in your code. Job definitions (tasks)
are version controlled like any other feature of your application. A single Cron
entry (`schedule:run` command) on your server running every minute executes due
tasks.

The inspiration and some of the API/code for this Bundle comes from [Laravel's
Task Scheduling feature](https://laravel.com/docs/master/scheduling).

1. [Installation](#installation)
2. [Quick Start](#quick-start)
3. [Defining the Schedule](define-schedule.md)
    1. [Your Kernel](define-schedule.md#your-kernel)
    2. [Self-Scheduling Commands](define-schedule.md#self-scheduling-commands)
    3. [ScheduleBuilder Service](define-schedule.md#schedulebuilder-service)
    4. [Schedule Hooks](define-schedule.md#schedule-hooks)
        1. [Filters](define-schedule.md#filters)
        2. [Callbacks](define-schedule.md#callbacks)
        3. [Ping Webhook](define-schedule.md#ping-webhook)
        4. [Email On Failure](define-schedule.md#email-on-failure)
        5. [Run on Single Server](define-schedule.md#run-on-single-server)
        6. [Limit to specific environment(s)](define-schedule.md#limit-to-specific-environments)
        7. [Example](define-schedule.md#example)
4. [Defining Tasks](define-tasks.md)
    1. [Task Types](define-tasks.md#task-types)
        1. [CommandTask](define-tasks.md#commandtask)
        2. [CallbackTask](define-tasks.md#callbacktask)
        3. [ProcessTask](define-tasks.md#processtask)
        4. [NullTask](define-tasks.md#nulltask)
        5. [CompoundTask](define-tasks.md#compoundtask)
    2. [Task Description](define-tasks.md#task-description)
    3. [Frequency Options](define-tasks.md#frequency-options)
    4. [Timezone](define-tasks.md#timezone)
    5. [Task Hooks](define-tasks.md#task-hooks)
        1. [Filters](define-tasks.md#filters)
        2. [Callbacks](define-tasks.md#callbacks)
        3. [Ping Webhook](define-tasks.md#ping-webhook)
        4. [Email Output](define-tasks.md#email-output)
        5. [Prevent Overlap](define-tasks.md#prevent-overlap)
        6. [Run on Single Server](define-tasks.md#run-on-single-server)
        7. [Between](define-tasks.md#between)
        8. [Example](define-tasks.md#example)
5. [CLI Commands](cli-commands.md)
    1. [schedule:list](cli-commands.md#schedulelist)
    2. [schedule:run](cli-commands.md#schedulerun)
6. [Extending](extending.md)
    1. [Custom Tasks](extending.md#custom-tasks)
    2. [Custom Extensions](extending.md#custom-extensions)
    3. [Events](extending.md#events)
7. [Full Configuration Reference](#full-configuration-reference)

## Installation

### Applications that use Symfony Flex

```console
$ composer require zenstruck/schedule-bundle
```

### Applications that don't use Symfony Flex

1. Download the Bundle

    ```console
    $ composer require zenstruck/schedule-bundle
    ```

2. Enable the Bundle

    ```php
    // config/bundles.php
    
    return [
        // ...
        Zenstruck\ScheduleBundle\ZenstruckScheduleBundle::class => ['all' => true],
    ];
    ```

## Quick Start

1. Have your `Kernel` implement [`ScheduleBuilder`](../src/Schedule/ScheduleBuilder.php)
   and add tasks to your schedule:

    ```php
    // src/Kernel.php
    
    use Zenstruck\ScheduleBundle\Schedule;
    use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
    // ...
    
    class Kernel extends BaseKernel implements ScheduleBuilder
    {
        public function buildSchedule(Schedule $schedule): void
        {
            $schedule
                ->onSingleServer()
                ->emailOnFailure('admin@example.com')
            ;
   
            $schedule->addCommand('app:send-weekly-report')
                ->description('Send the weekly report to users.')
                ->sundays()
                ->at(1)
                ->emailOnFailure('admin@example.com')
                ->pingOnSuccess('https://www.example.com/weekly-report-healthcheck')
            ;
    
            $schedule->addCommand('app:send-hourly-report', '--to=accounting@example.com', '--to=sales@example.com')
                ->hourly()
                ->between(9, 5)
                ->withoutOverlapping()
                ->emailOnFailure('admin@example.com')
                ->pingOnSuccess('https://www.example.com/hourly-report-healthcheck')
            ;
        }
    
        // ...
    }
    ```
   
2. List your tasks to diagnose any problems:

    ```console
    $ bin/console schedule:list
    ```

3. Add the following Cron job on your server running every minute:

    ```
    * * * * * cd /path-to-your-project && bin/console schedule:run >> /dev/null 2>&1
    ```

## Full Configuration Reference

```yaml
zenstruck_schedule:

    # The LockFactory service to use
    without_overlapping_handler: null # Example: lock.default.factory

    # The LockFactory service to use - be sure to use a "remote store" (https://symfony.com/doc/current/components/lock.html#remote-stores)
    single_server_handler: null # Example: lock.redis.factory

    # The HttpClient service to use
    ping_handler:         null # Example: http_client

    # The timezone for tasks (override at task level), null for system default
    timezone:             null # Example: America/New_York
    email_handler:
        enabled:              false

        # The mailer service to use
        service:              mailer

        # The default "from" email address (use if no mailer default from is configured)
        default_from:         null

        # The default "to" email address (can be overridden by extension)
        default_to:           null

        # The prefix to use for email subjects (use to distinguish between different application schedules)
        subject_prefix:       null # Example: "[Acme Inc Website]"
    schedule_extensions:

        # Set the environment(s) you only want the schedule to run in.
        environments:         [] # Example: [prod, staging]

        # Run schedule on only one server
        on_single_server:
            enabled:              false

            # Maximum expected lock duration in seconds
            ttl:                  3600

        # Send email if schedule fails
        email_on_failure:
            enabled:              false

            # Email address to send email to (leave blank to use "zenstruck_schedule.email_handler.default_to")
            to:                   null

            # Email subject (leave blank to use extension default)
            subject:              null

        # Ping a url before schedule runs
        ping_before:
            enabled:              false

            # The url to ping
            url:                  ~ # Required

            # The HTTP method to use
            method:               GET

            # See HttpClientInterface::OPTIONS_DEFAULTS
            options:              []

        # Ping a url after schedule runs
        ping_after:
            enabled:              false

            # The url to ping
            url:                  ~ # Required

            # The HTTP method to use
            method:               GET

            # See HttpClientInterface::OPTIONS_DEFAULTS
            options:              []

        # Ping a url if the schedule successfully ran
        ping_on_success:
            enabled:              false

            # The url to ping
            url:                  ~ # Required

            # The HTTP method to use
            method:               GET

            # See HttpClientInterface::OPTIONS_DEFAULTS
            options:              []

        # Ping a url if the schedule failed
        ping_on_failure:
            enabled:              false

            # The url to ping
            url:                  ~ # Required

            # The HTTP method to use
            method:               GET

            # See HttpClientInterface::OPTIONS_DEFAULTS
            options:              []
```
