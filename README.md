# The ScheduleBundle

[![CI](https://github.com/zenstruck/schedule-bundle/workflows/CI/badge.svg)](https://github.com/zenstruck/schedule-bundle/actions?query=workflow%3ACI)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/zenstruck/schedule-bundle/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/zenstruck/schedule-bundle/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/zenstruck/schedule-bundle/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/zenstruck/schedule-bundle/?branch=master)

Schedule Cron jobs (commands/callbacks/bash scripts) within your Symfony
application. Most applications have jobs that need to run at specific intervals.
This bundle enables you to define these jobs in your code. Job definitions (tasks)
are version controlled like any other feature of your application. A single Cron
entry (`bin/console schedule:run`) on your server running every minute executes due
tasks.

The inspiration and some of the API/code for this Bundle comes from [Laravel's
Task Scheduling feature](https://laravel.com/docs/master/scheduling).

1. [Installation](#installation)
2. [Quick Start](#quick-start)
3. [Defining the Schedule](doc/define-schedule.md)
    1. [ScheduleBuilder Service](doc/define-schedule.md#schedulebuilder-service)
    2. [Your Kernel](doc/define-schedule.md#your-kernel)
    3. [Bundle Configuration](doc/define-schedule.md#bundle-configuration)
    4. [Self-Scheduling Commands](doc/define-schedule.md#self-scheduling-commands)
    5. [Timezone](doc/define-schedule.md#timezone)
    6. [Schedule Extensions](doc/define-schedule.md#schedule-extensions)
        1. [Filters](doc/define-schedule.md#filters)
        2. [Callbacks](doc/define-schedule.md#callbacks)
        3. [Ping Webhook](doc/define-schedule.md#ping-webhook)
        4. [Email On Failure](doc/define-schedule.md#email-on-failure)
        5. [Run on Single Server](doc/define-schedule.md#run-on-single-server)
        6. [Limit to specific environment(s)](doc/define-schedule.md#limit-to-specific-environments)
4. [Defining Tasks](doc/define-tasks.md)
    1. [Task Types](doc/define-tasks.md#task-types)
        1. [CommandTask](doc/define-tasks.md#commandtask)
        2. [CallbackTask](doc/define-tasks.md#callbacktask)
        3. [ProcessTask](doc/define-tasks.md#processtask)
        3. [PingTask](doc/define-tasks.md#pingtask)
        4. [CompoundTask](doc/define-tasks.md#compoundtask)
    2. [Task Description](doc/define-tasks.md#task-description)
    3. [Frequency](doc/define-tasks.md#frequency)
        1. [Cron Expression](doc/define-tasks.md#cron-expression)
        2. [Fluent Expression Builder](doc/define-tasks.md#fluent-expression-builder)
        3. [Hashed Cron Expression](doc/define-tasks.md#hashed-cron-expression)
    4. [Task ID](doc/define-tasks.md#task-id)
    5. [Timezone](doc/define-tasks.md#timezone)
    6. [Task Extensions](doc/define-tasks.md#task-extensions)
        1. [Filters](doc/define-tasks.md#filters)
        2. [Callbacks](doc/define-tasks.md#callbacks)
        3. [Ping Webhook](doc/define-tasks.md#ping-webhook)
        4. [Email Output](doc/define-tasks.md#email-output)
        5. [Prevent Overlap](doc/define-tasks.md#prevent-overlap)
        6. [Run on Single Server](doc/define-tasks.md#run-on-single-server)
        7. [Between](doc/define-tasks.md#between)
5. [Running the Schedule](doc/run-schedule.md)
    1. [Cron Job on Server](doc/run-schedule.md#cron-job-on-server)
    2. [Symfony Cloud](doc/run-schedule.md#symfony-cloud)
    3. [Alternatives](doc/run-schedule.md#alternatives)
    4. [Force Run](doc/run-schedule.md#force-run)
    5. [Dealing with Failures](doc/run-schedule.md#dealing-with-failures)
    6. [Ensuring Schedule is Running](doc/run-schedule.md#ensuring-the-schedule-is-running)
6. [CLI Commands](doc/cli-commands.md)
    1. [schedule:list](doc/cli-commands.md#schedulelist)
    2. [schedule:run](doc/cli-commands.md#schedulerun)
7. [Extending](doc/extending.md)
    1. [Custom Tasks](doc/extending.md#custom-tasks)
    2. [Custom Extensions](doc/extending.md#custom-extensions)
    3. [Events](doc/extending.md#events)
8. [Full Configuration Reference](#full-configuration-reference)

## Installation

```console
$ composer require zenstruck/schedule-bundle
```

*If not using Symfony Flex, be sure to enable the bundle.*

## Quick Start

1. Add your schedule service (assumes *autowire* and *autoconfiguration* enabled):

    ```php
    // src/Schedule/AppScheduleBuilder.php

    use Zenstruck\ScheduleBundle\Schedule;
    use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

    class AppScheduleBuilder implements ScheduleBuilder
    {
        public function buildSchedule(Schedule $schedule): void
        {
            $schedule
                ->timezone('UTC')
                ->environments('prod')
            ;

            $schedule->addCommand('app:send-weekly-report --detailed')
                ->description('Send the weekly report to users.')
                ->sundays()
                ->at(1)
            ;
   
            // ...
        }
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

See [Defining the Schedule](doc/define-schedule.md) and [Defining Tasks](doc/define-tasks.md)
for more options.

## Full Configuration Reference

```yaml
zenstruck_schedule:

    # The LockFactory service to use for the without overlapping extension
    without_overlapping_lock_factory: null # Example: lock.default.factory

    # The LockFactory service to use for the single server extension - be sure to use a "remote store" (https://symfony.com/doc/current/components/lock.html#remote-stores)
    single_server_lock_factory: null # Example: lock.redis.factory

    # The HttpClient service to use
    http_client:          null # Example: http_client

    # The default timezone for tasks (override at task level), null for system default
    timezone:             null # Example: America/New_York

    mailer:
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

        # Send email if schedule fails (alternatively enable by passing a "to" email)
        email_on_failure:
            enabled:              false

            # Email address to send email to (leave blank to use "zenstruck_schedule.mailer.default_to")
            to:                   null

            # Email subject (leave blank to use extension default)
            subject:              null

        # Ping a url before schedule runs (alternatively enable by passing a url)
        ping_before:
            enabled:              false

            # The url to ping
            url:                  ~ # Required

            # The HTTP method to use
            method:               GET

            # See HttpClientInterface::OPTIONS_DEFAULTS
            options:              []

        # Ping a url after schedule runs (alternatively enable by passing a url)
        ping_after:
            enabled:              false

            # The url to ping
            url:                  ~ # Required

            # The HTTP method to use
            method:               GET

            # See HttpClientInterface::OPTIONS_DEFAULTS
            options:              []

        # Ping a url if the schedule successfully ran (alternatively enable by passing a url)
        ping_on_success:
            enabled:              false

            # The url to ping
            url:                  ~ # Required

            # The HTTP method to use
            method:               GET

            # See HttpClientInterface::OPTIONS_DEFAULTS
            options:              []

        # Ping a url if the schedule failed (alternatively enable by passing a url)
        ping_on_failure:
            enabled:              false

            # The url to ping
            url:                  ~ # Required

            # The HTTP method to use
            method:               GET

            # See HttpClientInterface::OPTIONS_DEFAULTS
            options:              []
    tasks:

        # Example:
        - 
            task:                send:sales-report --detailed
            frequency:           '0 * * * *'
            description:         Send sales report hourly
            without_overlapping: ~
            only_between:        9-17
            ping_on_success:     https://example.com/hourly-report-health-check
            email_on_failure:    sales@example.com

        # Prototype
        -

            # Defaults to CommandTask, prefix with "bash:" to create ProcessTask, prefix url with "ping:" to create PingTask, pass array of commands to create CompoundTask (optionally keyed by description)
            task:                 ~ # Required, Example: "my:command arg1 --option1=value", "bash:/bin/my-script" or "ping:https://example.com"

            # Cron expression
            frequency:            ~ # Required, Example: '0 * * * *'

            # Task description
            description:          null

            # The timezone for this task, null for system default
            timezone:             null # Example: America/New_York

            # Prevent task from running if still running from previous run
            without_overlapping:
                enabled:              false

                # Maximum expected lock duration in seconds
                ttl:                  86400

            # Only run between given times (alternatively enable by passing a range, ie "9:00-17:00"
            only_between:
                enabled:              false
                start:                ~ # Required, Example: 9:00
                end:                  ~ # Required, Example: 17:00

            # Skip when between given times (alternatively enable by passing a range, ie "17:00-06:00"
            unless_between:
                enabled:              false
                start:                ~ # Required, Example: 17:00
                end:                  ~ # Required, Example: 06:00

            # Ping a url before task runs (alternatively enable by passing a url)
            ping_before:
                enabled:              false

                # The url to ping
                url:                  ~ # Required

                # The HTTP method to use
                method:               GET

                # See HttpClientInterface::OPTIONS_DEFAULTS
                options:              []

            # Ping a url after task runs (alternatively enable by passing a url)
            ping_after:
                enabled:              false

                # The url to ping
                url:                  ~ # Required

                # The HTTP method to use
                method:               GET

                # See HttpClientInterface::OPTIONS_DEFAULTS
                options:              []

            # Ping a url if the task successfully ran (alternatively enable by passing a url)
            ping_on_success:
                enabled:              false

                # The url to ping
                url:                  ~ # Required

                # The HTTP method to use
                method:               GET

                # See HttpClientInterface::OPTIONS_DEFAULTS
                options:              []

            # Ping a url if the task failed (alternatively enable by passing a url)
            ping_on_failure:
                enabled:              false

                # The url to ping
                url:                  ~ # Required

                # The HTTP method to use
                method:               GET

                # See HttpClientInterface::OPTIONS_DEFAULTS
                options:              []

            # Send email after task runs (alternatively enable by passing a "to" email)
            email_after:
                enabled:              false

                # Email address to send email to (leave blank to use "zenstruck_schedule.mailer.default_to")
                to:                   null

                # Email subject (leave blank to use extension default)
                subject:              null

            # Send email if task fails (alternatively enable by passing a "to" email)
            email_on_failure:
                enabled:              false

                # Email address to send email to (leave blank to use "zenstruck_schedule.mailer.default_to")
                to:                   null

                # Email subject (leave blank to use extension default)
                subject:              null
```
