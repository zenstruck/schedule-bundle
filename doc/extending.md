# Extending

## Custom Tasks

You can define your own task types. Tasks consist of a *task* object that extends
[`Task`](../src/Schedule/Task.php) and a *runner* that implements
[`TaskRunner`](../src/Schedule/Task/TaskRunner.php). The runner is responsible
for running the command and returning a [`Result`](../src/Schedule/Task/Result.php).
If your task is capable of running itself, have it implement
[`SelfRunningTask`](../src/Schedule/Task/SelfRunningTask.php) (a *runner* is not required).

If your task requires a *runner*, the runner must be a service with the `schedule.task_runner` tag
(this is autoconfigurable). Runners must implement the `supports()` method which should return
true when passed the task it handles.

See [`CallbackTask`](../src/Schedule/Task/CallbackTask.php) for an example of a *self-running*
task and [`CommandTask`](../src/Schedule/Task/CommandTask.php) for an example of a task with
a *[runner](../src/Schedule/Task/Runner/CommandTaskRunner.php)*.

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

        // be sure to call the parent constructor with a default description
        parent::__construct(get_class($message));
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
use App\Message\DoSomething;
use App\Schedule\MessageTask;

/* @var $schedule \Zenstruck\ScheduleBundle\Schedule */

$schedule->add(new MessageTask(new DoSomething()))
    ->daily()
    ->at('13:30')
;
```

## Custom Extensions

The primary way of hooking into schedule/task events is with extensions. Extensions
can be added to both tasks and the schedule as a whole. Extensions must implement
[`Extension`](../src/Schedule/Extension.php) and require a *handler* than extends
[`ExtensionHandler`](../src/Schedule/Extension/ExtensionHandler.php). If your
extension is capable of handling itself, the extension can extend
[`SelfHandlingExtension`](../src/Schedule/Extension/SelfHandlingExtension.php) (a
handler is not required). Override the methods that are applicable to your extension.

If your extension requires a *handler*, the handler must be a service with the
`schedule.extension_handler` tag (this is autoconfigurable). Extension handlers must
implement the `supports()` method which should return true when passed the extension
it handles.

See [`CallbackExtension`](../src/Schedule/Extension/CallbackExtension.php) for an example
of a *self-handling* extension and
[`EnvironmentExtension`](../src/Schedule/Extension/EnvironmentExtension.php)
for an example of an extension with a
*[handler](../src/Schedule/Extension/Handler/EnvironmentHandler.php)*.

If your extension is applicable to the schedule, you can auto-add it by registering
it as a service and adding the `schedule.extension` tag (autoconfiguration is not
available).

Below are some examples of custom extensions:

### Example 1: Skip Schedule if in maintenance mode

Say your application has the concept of maintenance mode. You want to prevent the
schedule from running in maintenance mode. 

This example assumes your `Kernel` has an `isInMaintenanceMode()` method.

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

The *handler* service:

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

The easiest way to add this extension to your schedule is to register the *extension*
(`App\Schedule\Extension\NotInMaintenanceMode`) as a service and tag it with
`schedule.extension`.

Alternatively, you can add it to the schedule in PHP:

```php
use App\Schedule\Extension\NotInMaintenanceMode;

/* @var $schedule \Zenstruck\ScheduleBundle\Schedule */

$schedule->addExtension(new NotInMaintenanceMode());
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
use App\Schedule\Extension\SendFailingTaskToWebhook;

/* @var $task \Zenstruck\ScheduleBundle\Schedule\Task */

$task->addExtension(new SendFailingTaskToWebhook('https://example.com/failing-task'));
```

## Events

The following Symfony events are available:

| Event                                                         | Description                   |
| ------------------------------------------------------------- | ----------------------------- |
| [`BeforeScheduleEvent`](../src/Event/BeforeScheduleEvent.php) | Runs before the schedule runs |
| [`AfterScheduleEvent`](../src/Event/AfterScheduleEvent.php)   | Runs after the schedule runs  |
| [`BeforeTaskEvent`](../src/Event/BeforeTaskEvent.php)         | Runs before task runs         |
| [`AfterTaskEvent`](../src/Event/AfterTaskEvent.php)           | Runs after task runs          |
| [`ScheduleBuildEvent`](../src/Event/ScheduleBuildEvent.php)   | Defining your schedule        |

### Example: Add "withoutOverlapping" to all defined tasks

Let's configure all our tasks to have the [withoutOverlapping](define-tasks.md#prevent-overlap)
extension added.

The *subscriber* service:

```php
// src/EventSubscriber/ScheduleWithoutOverlappingSubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\ScheduleBuildEvent;

class ScheduleWithoutOverlappingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ScheduleBuildEvent::class => [
                'onScheduleBuildEvent',
                /*
                 * The actual building of the schedule happens at priority "0".
                 * We set to a lower priority to ensure all tasks are defined.
                 */
                -100,
            ],
        ];
    }

    public function onScheduleBuildEvent(ScheduleBuildEvent $event): void
    {
        foreach ($event->getSchedule()->all() as $task) {
            $task->withoutOverlapping();
        }
    }
}
```

**NOTE:** If *autoconfiguration* is not enabled, add the `kernel.event_subscriber`
tag to the service.
