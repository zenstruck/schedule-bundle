# Running the Schedule

To run tasks when they are due, the schedule should be *run* **every minute**
on your production server(s) indefinitely.

*The schedule doesn't have to be run every minute but if it isn't, jobs
scheduled in between the frequency you choose will never run. If you are
careful when choosing task frequencies, this might not be an issue. If not
running every minute, it must be run at predictable times like every hour,
exactly on the hour (ie 08:00, not 08:01).*

If multiple tasks are due at the same time, they are run synchronously in the
order they were defined. If you define tasks in multiple places
([Configuration](define-schedule.md#bundle-configuration),
[Builder Service](define-schedule.md#schedulebuilder-service),
[Kernel](define-schedule.md#your-kernel),
[Self-Scheduling Commands](define-schedule.md#self-scheduling-commands)) only
the order of tasks defined in each place is guaranteed.

Shipped with this bundle is a [`schedule:run`](cli-commands.md#schedulerun)
console command. Running this command determines the due tasks (if any) for
the current time and runs them.

## Cron Job on Server

The most common way to run the schedule is a Cron job that runs the
[`schedule:run`](cli-commands.md#schedulerun) every minute. The following
should be added to your production server's
[crontab](http://man7.org/linux/man-pages/man5/crontab.5.html):

```
* * * * * cd /path-to-your-project && php bin/console schedule:run >> /dev/null 2>&1
```

## Symfony Cloud

The [Symfony Cloud](https://symfony.com/cloud/) platform has the ability to
configure Cron jobs. Add the following configuration to run your schedule every
minute:

```yaml
# .symfony.cloud.yaml

cron:
    spec: * * * * *
    cmd: bin/console schedule:run

# ...
```

*[View the full Cron Jobs Documentation](https://symfony.com/doc/master/cloud/cookbooks/crons.html)*

## Alternatives

If you don't have the ability to run Cron jobs on your server there may be
other ways to run the schedule.

The schedule can alternatively be run in your code. Behind the scenes, the
`schedule:run` command invokes the [`ScheduleRunner`](../src/Schedule/ScheduleRunner.php)
service which does all the work. The return value of `ScheduleRunner::__invoke()` is a
[`ScheduleRunContext`](../src/Schedule/ScheduleRunContext.php) object.

The following is a list of alternative scheduling options (*please add your own solutions
via PR*):

### Webhook

Perhaps you have a service that can *ping* an endpoint (`/run-schedule`) defined in
your app every minute (AWS Lamda can be configured to do this). This endpoint
can run the schedule:

```php
// src/Controller/RunScheduleController.php

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunner;

/**
 * @Route("/run-schedule")
*/
class RunScheduleController
{
    public function __invoke(ScheduleRunner $scheduleRunner): Response
    {
        $result = $scheduleRunner();

        return new Response('', $result->isSuccessful() ? 200 : 500);
    }
}
```

## Force Run

The [`schedule:run`](cli-commands.md#schedulerun) command optionally takes
a list of [Task ID's](define-tasks.md#task-id). This will force run these
tasks (and no others) even if they are not currently due. This can be useful
for re-running tasks that [fail](#dealing-with-failures). The task ID is show
in emails/logs and listed in [`schedule:list --detail`](cli-commands.md#schedulelist).

## Dealing with Failures

It is probable that at some point, a scheduled task will fail. Because the
schedule runs in the background, administrators need to be made aware failures.

*If multiple tasks are due at the same time, one failure will not prevent the
other due tasks from running.*

A failing task may or may not be the result of an exception. For instance, a
[CommandTask](define-tasks.md#commandtask) that ran with an exit code of `1`
is considered failed but may not be from the result of an exception (the
command could have returned `1`).

The following are different methods of being alerted to failures:

### Logs

All schedule/task events are logged (if using monolog, on the `schedule` channel).
Errors and Exceptions are logged with the `ERROR` and `CRITICAL` levels respectively.
The log's context contains useful information like duration, memory usage, task output
and the exception (if failed).

The following is an example log file (some context excluded):

```
[2020-01-20 13:17:13] schedule.INFO: Running 4 due tasks. {"total":22,"due":4}
[2020-01-20 13:17:13] schedule.INFO: Running "CommandTask": my:command
[2020-01-20 13:17:13] schedule.INFO: Successfully ran "CommandTask": my:command
[2020-01-20 13:17:13] schedule.INFO: Running "ProcessTask": fdere -dsdfsd
[2020-01-20 13:17:13] schedule.ERROR: Failure when running "ProcessTask": fdere -dsdfsd
[2020-01-20 13:17:13] schedule.INFO: Running "CallbackTask": some callback 
[2020-01-20 13:17:13] schedule.CRITICAL: Exception thrown when running "CallbackTask": some callback
[2020-01-20 13:24:11] schedule.INFO: Running "CommandTask": another:command
[2020-01-20 13:24:11] schedule.INFO: Skipped "CommandTask": another:command {"reason":"the reason for skip..."}
[2020-01-20 13:24:11] schedule.ERROR: 3/4 tasks ran {"total":4,"successful":1,"skipped":1,"failures":2,"duration":"< 1 sec","memory":"10.0 MiB"}
```

Services like [Papertrail](https://papertrailapp.com) can be [configured to alert
administrators](https://help.papertrailapp.com/kb/how-it-works/alerts/) when a filter
(ie `schedule.ERROR OR schedule.CRITICAL`) is matched.

### Email on Schedule Failure

Administrators can be notified via email when tasks fail. This can be configured
[per task](define-tasks.md#email-output) or
[for the entire schedule](define-schedule.md#email-on-failure).

### `schedule:run` exit code/output

The [`schedule:run`](cli-commands.md#schedulerun) command will have an exit code of
`1` if one or more tasks fail. The command's output also contains detailed output.
The crontab entry [shown above](#cron-job-on-server) ignores the exit code and
dumps the command's output to `/dev/null` but this could be changed to log the
output and/or alert an administrator.

### Alert with Symfony Cloud

When defining the `schedule:run` cron job with [Symfony Cloud](#symfony-cloud), you can
[prefix the command with `croncape` to be alerted via email](https://symfony.com/doc/master/cloud/cookbooks/crons.html#command-to-run)
when something goes wrong:

```yaml
# .symfony.cloud.yaml

cron:
    spec: * * * * *
    cmd: croncape bin/console schedule:run

# ...
```

### Custom Schedule Extension

You can create a [custom schedule extension](extending.md#custom-extensions) with a
`onScheduleFailure` hook to add your own failure logic.

### AfterSchedule Event

You can [create an event subscriber](extending.md#events) that listens to the
[`AfterScheduleEvent`](../src/Event/AfterScheduleEvent.php), check if the schedule
failed, and run your own failure logic.

## Ensuring the Schedule is Running

It is important to be assured your schedule is always running. The best method
is to use a Cron health monitoring tool like [Cronitor](https://cronitor.io/),
[Laravel Envoyer](https://envoyer.io/) or [Healthchecks](https://healthchecks.io/).
These services give you a unique URL endpoint to *ping*. If the endpoint doesn't
receive a ping after a specified amount of time, an administrator is notified.

You can [configure your schedule to ping](define-schedule.md#ping-webhook) after
running (assumes your endpoint is `https://my-health-monitor.com/endpoint`):

```yaml
# config/packages/zenstruck_schedule.yaml

zenstruck_schedule:
    schedule_extensions:
        ping_after: https://my-health-monitor.com/endpoint
```

This will ping the endpoint after the schedule runs (every minute). If this is too
frequent, you can configure a *[PingTask](define-tasks.md#pingtask)* to ping the
endpoint at a different frequency:

```yaml
zenstruck_schedule:
    tasks:
        -   task: ping:https://my-health-monitor/endpoint
            description: Health check
            frequency: '@hourly'
```

In this case, a notification from one of these services means your schedule isn't
running.
