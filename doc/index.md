# The ScheduleBundle

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
3. [Defining the Schedule](define-schedule.md)
    1. [Bundle Configuration](define-schedule.md#bundle-configuration)
    2. [ScheduleBuilder Service](define-schedule.md#schedulebuilder-service)
    3. [Self-Scheduling Commands](define-schedule.md#self-scheduling-commands)
    4. [Your Kernel](define-schedule.md#your-kernel)
    5. [Timezone](define-schedule.md#timezone)
    6. [Schedule Extensions](define-schedule.md#schedule-extensions)
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
        4. [CompoundTask](define-tasks.md#compoundtask)
        5. [NullTask](define-tasks.md#nulltask)
    2. [Task Description](define-tasks.md#task-description)
    3. [Frequency](define-tasks.md#frequency)
        1. [Cron Expression](define-tasks.md#cron-expression)
        2. [Fluent Expression Builder](define-tasks.md#fluent-expression-builder)
        2. [Hashed Cron Expression](define-tasks.md#hashed-cron-expression)
    4. [Timezone](define-tasks.md#timezone)
    5. [Task Extensions](define-tasks.md#task-extensions)
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

1. Add tasks and schedule configuration to your bundle config:

    ```yaml
    # config/packages/zenstruck_schedule.yaml

    zenstruck_schedule:
        email_handler: # enable email notifications
            default_from: webmaster@example.com
            default_to: admin@example.com

        timezone: America/New_York # all tasks will run in this timezone

        schedule_extensions:
            environiments: prod # only run when in production
            email_on_failure: ~ # send email if some tasks fail

        tasks:
            -   command: app:send-weekly-report
                frequency: 0 * * * 0 # Sundays @ 1am
                email_on_failure: ~ # send email if this task fails
                ping_on_success: https://www.example.com/weekly-report-healthcheck
    
            -   command: app:send-hourly-report --to=accounting@example.com --to=sales@example.com
                frequency: 0 * * * 1-5 # Hourly on weekdays
                between: 9-17 # only between 9am and 5pm
                without_overlapping: ~ # prevent running over itself
                ping_on_success: https://www.example.com/hourly-report-healthcheck
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

    # The default timezone for tasks (override at task level), null for system default
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

        # Send email if schedule fails (alternatively enable by passing a "to" email)
        email_on_failure:
            enabled:              false

            # Email address to send email to (leave blank to use "zenstruck_schedule.email_handler.default_to")
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
            command:             send:sales-report --detailed
            frequency:           0 * * * *
            description:         Send sales report hourly
            without_overlapping: ~
            between:             9-17
            ping_on_success:     https://example.com/hourly-report-health-check
            email_on_failure:    sales@example.com

        # Prototype
        -

            # Defaults to CommandTask, prefix with "bash:" to create ProcessTask, pass (null) to create NullTask, pass array of commands to create CompoundTask (optionally keyed by description)
            command:              ~ # Required, Example: "my:command arg1 --option1=value" or "bash:/bin/my-script"

            # Cron expression
            frequency:            ~ # Required, Example: 0 * * * *

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
            between:
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

                # Email address to send email to (leave blank to use "zenstruck_schedule.email_handler.default_to")
                to:                   null

                # Email subject (leave blank to use extension default)
                subject:              null

            # Send email if task fails (alternatively enable by passing a "to" email)
            email_on_failure:
                enabled:              false

                # Email address to send email to (leave blank to use "zenstruck_schedule.email_handler.default_to")
                to:                   null

                # Email subject (leave blank to use extension default)
                subject:              null
```
