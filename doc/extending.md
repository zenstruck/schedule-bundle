# Extending

## Custom Tasks

You can define your own task types. Tasks consist of a *task* object that extends
[`Task`](../src/Schedule/Task.php) and a *runner* that implements
[`TaskRunner`](../src/Schedule/Task/TaskRunner.php). The runner is responsible
for running the command and returning a *result* ([`Result`](../src/Schedule/Task/Result.php)).
If your task is capable of running itself, have your task implement
[`SelfRunningTask`](../src/Schedule/Task/SelfRunningTask.php) (a *runner* is not required).
See [`CallbackTask`](../src/Schedule/Task/CallbackTask.php) for an example of a *self-running*
task and [`CommandTask`](../src/Schedule/Task/CommandTask.php) for an example of a task with
a *runner*.

If your task requires a *runner*, the runner must be a service with the `schedule.task_runner` tag
(this is autoconfigurable).

As an example, let's create a Task that sends a *Message* to your *MessageBus* (`symfony/messenger`
required).

First, let's create the task:

```php
// src/Schedule/MessageTask.php

use Zenstruck\ScheduleBundle\Schedule\Task;

class MessageTask extends Task
{
    private $message;

    public function __construct(object $message)
    {
        $this->message = $message;

        parent::__construct(get_class($message)); // be sure to call the parent constructor with a default description
    }

    public function getMessage(): object
    {
        return $this->message;
    }
}
```

Next, let's create the *runner*:

```php
// src/Schedule/MessageTaskRunner.php

use Symfony\Component\Messenger\MessageBusInterface;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

class MessageTaskRunner implements TaskRunner
{
    private $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    /**
     * @param MessageTask $task
     */
    public function __invoke(Task $task): Result
    {
        $this->bus->dispatch($task->getMessage());

        return Result::successful($task);
    }

    public function supports(Task $task) : bool
    {
        return $task instanceof MessageTask;
    }
}
```

Finally, use this task in your schedule:

```php
// src/Kernel.php

use App\Message\DoSomething;
use App\Schedule\MessageTask;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
// ...

class Kernel extends BaseKernel implements ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void
    {
        $schedule->add(new MessageTask(new DoSomething()))
            ->daily()
            ->at('13:30')
        ;
    }

    // ...
}
```

## Custom Extensions

The primary way of hooking into schedule/task events is with extensions. Extensions
can be added to both tasks and the schedule as a whole. Extensions must implement
[`Extension`](../src/Schedule/Extension.php) and require a *handler* than extends
[`ExtensionHandler`](../src/Schedule/Extension/ExtensionHandler.php). If your
extension is capable of handling itself, the extension can extend
[`SelfHandlingExtension`](../src/Schedule/Extension/SelfHandlingExtension.php) (a
handler is not required).

If your extension requires a *handler*, the handler must be a service with the
`schedule.extension_handler` tag (this is autoconfigurable).

Extension handlers must implement the `supports()` method and have the following
methods available (they do nothing by default):

```php
/**
 * Skip entire schedule if \Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule
 * exception is thrown.
 */
public function filterSchedule(BeforeScheduleEvent $event, Extension $extension): void

/**
 * Executes before the schedule runs.
 */
public function beforeSchedule(BeforeScheduleEvent $event, Extension $extension): void

/**
 * Executes after the schedule runs.
 */
public function afterSchedule(AfterScheduleEvent $event, Extension $extension): void

/**
 * Executes if the schedule ran with no failures.
 */
public function onScheduleSuccess(AfterScheduleEvent $event, Extension $extension): void

/**
 * Executes if the schedule ran with failures.
 */
public function onScheduleFailure(AfterScheduleEvent $event, Extension $extension): void

/**
 * Skip task if \Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask exception
 * is thrown.
 */
public function filterTask(BeforeTaskEvent $event, Extension $extension): void

/**
 * Executes before the task runs (not if skipped).
 */
public function beforeTask(BeforeTaskEvent $event, Extension $extension): void

/**
 * Executes after the task runs (not if skipped).
 */
public function afterTask(AfterTaskEvent $event, Extension $extension): void

/**
 * Executes if the task ran successfully (not if skipped).
 */
public function onTaskSuccess(AfterTaskEvent $event, Extension $extension): void

/**
 * Executes if the task failed (not if skipped).
 */
public function onTaskFailure(AfterTaskEvent $event, Extension $extension): void
```

[`SelfHandling`](../src/Schedule/Extension/SelfHandlingExtension.php) extensions have the
following methods available (they do nothing by default):

```php
/**
 * Skip entire schedule if \Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule
 * exception is thrown.
 */
public function filterSchedule(BeforeScheduleEvent $event): void

/**
 * Executes before the schedule runs.
 */
public function beforeSchedule(BeforeScheduleEvent $event): void

/**
 * Executes after the schedule runs.
 */
public function afterSchedule(AfterScheduleEvent $event): void

/**
 * Executes if the schedule ran with no failures.
 */
public function onScheduleSuccess(AfterScheduleEvent $event): void

/**
 * Executes if the schedule ran with failures.
 */
public function onScheduleFailure(AfterScheduleEvent $event): void

/**
 * Skip task if \Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask exception
 * is thrown.
 */
public function filterTask(BeforeTaskEvent $event): void

/**
 * Executes before the task runs (not if skipped).
 */
public function beforeTask(BeforeTaskEvent $event): void

/**
 * Executes after the task runs (not if skipped).
 */
public function afterTask(AfterTaskEvent $event): void

/**
 * Executes if the task ran successfully (not if skipped).
 */
public function onTaskSuccess(AfterTaskEvent $event): void

/**
 * Executes if the task failed (not if skipped).
 */
public function onTaskFailure(AfterTaskEvent $event): void
```

Below are some examples of custom extensions:

### Example 1: Skip Schedule if in maintenance mode

Say your application has the concept of maintenance mode. You want to prevent the
schedule from running in maintenance mode. 

This example assumes your `Kernel` has a `isInMaintenanceMode()` method.

The *extension*:

```php
// src/Schedule/Extension/NotInMaintenanceMode.php

use Zenstruck\ScheduleBundle\Schedule\Extension;

class NotInMaintenanceMode implements Extension
{
    public function __toString(): string
    {
        return 'Do not run in maintenance mode.';
    }
}
```

The *handler*:

```php
// src/Schedule/Extension/NotInMaintenanceModeHandler.php

use App\Kernel;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;

class NotInMaintenanceModeHandler extends ExtensionHandler
{
    private $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function supports(Extension $extension) : bool
    {
        return $extension instanceof NotInMaintenanceMode;
    }

    /**
     * @param NotInMaintenanceMode $extension
     */
    public function filterSchedule(BeforeScheduleEvent $event,Extension $extension): void
    {
        if ($this->kernel->isInMaintenanceMode()) {
            throw new SkipSchedule('Does not run in maintenance mode.');
        }
    }
}
```

Add to your schedule:

```php
// src/Kernel.php

use App\Schedule\Extension\NotInMaintenanceMode;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
// ...

class Kernel extends BaseKernel implements ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void
    {
        $schedule->addExtension(new NotInMaintenanceMode());

        //
    }

    // ...
}
```

### Example 2: Send Failing Task Output to Webhook

This example assumes you have an webhook (*https://example.com/failing-task*) that can receive
failing task details.

The *extension*:

```php
// src/Schedule/Extension/SendFailingTaskToWebhook.php

use Symfony\Component\HttpClient\HttpClient;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Schedule\Extension\SelfHandlingExtension;

class SendFailingTaskToWebhook extends SelfHandlingExtension
{
    private $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function __toString(): string
    {
        return "Send failing task details to {$this->url}";
    }

    public function onTaskFailure(AfterTaskEvent $event): void
    {
        $task = $event->getTask();
        $result = $event->getResult();
    
        HttpClient::create()->request('GET', $this->url, [
            'json' => [
                'id' => $task->getId(),
                'type' => $task->getType(),
                'description' => $task->getDescription(),
                'result' => [
                    'description' => $result->getDescription(),
                    'output' => $result->getOutput(),
                    'exception' => $result->isException() ? (string) $result->getException() : null,
                ]
            ]
        ]);
    }
}
```

Add to a scheduled task:

```php
// src/Kernel.php

use App\Schedule\Extension\SendFailingTaskToWebhook;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
// ...

class Kernel extends BaseKernel implements ScheduleBuilder
{
    public function buildSchedule(Schedule $schedule): void
    {
        $schedule->addCommand('send-sales-report', '--hourly')
            ->weekdays()
            ->hourly()
            ->addExtension(new SendFailingTaskToWebhook('https://example.com/failing-task'))
        ;

        //
    }

    // ...
}
```

## Events

The following Symfony events are available:

| Event | Description |
| ----- | ----------- |
| [`BeforeScheduleEvent`](../src/Event/BeforeScheduleEvent.php) | Runs before the schedule runs |
| [`AfterScheduleEvent`](../src/Event/AfterScheduleEvent.php) | Runs after the schedule runs |
| [`BeforeTaskEvent`](../src/Event/BeforeTaskEvent.php) | Runs before task runs |
| [`AfterTaskEvent`](../src/Event/AfterTaskEvent.php) | Runs after task runs |
| [`ScheduleBuildEvent`](../src/Event/ScheduleBuildEvent.php) | *see below* |

### ScheduleBuildEvent

The [`ScheduleBuildEvent`](../src/Event/ScheduleBuildEvent.php) has two purposes:

1. Defining your schedule: use the `ScheduleBuildEvent::REGISTER` priority
(see [`ScheduleBuilderSubscriber`](../src/EventListener/ScheduleBuilderSubscriber.php) for an example)
2. Adjusting the schedule after it is build: use the `ScheduleBuildEvent::POST_REGISTER`
priority (see [`TimezoneSubscriber`](../src/EventListener/TimezoneSubscriber.php) for an example)
