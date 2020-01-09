# CLI Commands

## schedule:list

```
Description:
  List configured scheduled tasks

Usage:
  schedule:list [options]

Options:
      --detail          Show detailed task list
```

This command lists your currently defined schedule. It displays useful information
and potential issues. The `--detail` option gives even more detail.

Consider the following schedule definition:

```php
// src/Kernel.php

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
// ...

class Kernel extends BaseKernel implements ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void
    {
        $schedule->onSingleServer();
        
        $schedule->addCommand('send-sales-report', '--hourly')
            ->emailOnFailure('admin@example.com')
            ->withoutOverlapping()
        ;

        $schedule->addCommand('send-sales-report', 'daily')
            ->weekdays()
            ->at(1)
            ->pingOnSuccess('https://example.com/daily-sales-report')
        ;
    }

    // ...
}
```

Assuming the bundle has no configuration, running `schedule:list` shows the following output:

```console
$ bin/console schedule:list
```

![schedule:list with issues](images/schedule-list_issues.png)

Running with the `--detail` flag shows the following:

```console
$ bin/console schedule:list --detail
```

![schedule:list --detail with issues](images/schedule-list-detail_issues.png)

There are two issues that need to be resolved in the bundle config:

```yaml
# config/packages/zenstruck_schedule.yaml
zenstruck_schedule:
    single_server_handler: lock.default.factory # required to use "onSingleServer"
    email_handler: # required to use "emailOnFailure"
        service: mailer
        default_from: webmaster@example.com
```

Running now shows the following:

```console
$ bin/console schedule:list
```

![schedule:list](images/schedule-list.png)

Running with the `--detail` flag shows the following:

```console
$ bin/console schedule:list --detail
```

![schedule:list --detail](images/schedule-list-detail.png)

## schedule:run

```
Description:
  Runs scheduled tasks that are due

Usage:
  schedule:run

Help:
  Exit code 0: no tasks ran, schedule skipped or all tasks run were successful.
  Exit code 1: some of the tasks ran failed.
```

This is the command that runs currently due tasks. It should be added as a Cron
job to your production server running every minute:

```
* * * * * cd /path-to-your-project && bin/console schedule:run >> /dev/null 2>&1
```

The above Cron job sends the command output to `/dev/null` but the command does
produce output. Using the example from `schedule:list` above and assuming one of the
tasks are due at time of run, the command will output the following:

```console
$ bin/console schedule:list
```

![schedule:run](images/schedule-run.png)

Running the command with the verbose flag (`-v`) displays task output:

```console
$ bin/console schedule:list -v
```

![schedule:run -v](images/schedule-run-v.png)
