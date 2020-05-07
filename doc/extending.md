# Extending

## Custom Tasks

You can define your own task types. Tasks consist of a *task* object that extends
[`Task`](../src/Schedule/Task.php) and a *runner* that implements
[`TaskRunner`](../src/Schedule/Task/TaskRunner.php). The runner is responsible
for running the command and returning a [`Result`](../src/Schedule/Task/Result.php).

The runner must be a service with the `schedule.task_runner` tag (this is *autoconfigurable*).
Runners must implement the `supports()` method which should return true when passed the task
it handles.

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
can be added to both tasks and the schedule as a whole. Extensions are plain objects
and require a *handler* that extends
[`ExtensionHandler`](../src/Schedule/Extension/ExtensionHandler.php).

The handler must be a service with the `schedule.extension_handler` tag (this is
*autoconfigurable*). Extension handlers must implement the `supports()` method which
should return true when passed the extension it handles.

If your extension is applicable to the schedule, you can auto-add it by registering
it as a service and adding the `schedule.extension` tag (*autoconfiguration* is **not**
available).

Making your extension stringable by implementing `__toString` shows this value in the
[`schedule:list`](cli-commands.md#schedulelist) command.

Below are some examples of custom extensions:

### Example: Skip Schedule if in maintenance mode

Say your application has the concept of maintenance mode. You want to prevent the
schedule from running in maintenance mode. 

This example assumes your `Kernel` has an `isInMaintenanceMode()` method.

The *extension*:

```php
// src/Schedule/Extension/NotInMaintenanceMode.php

class NotInMaintenanceMode
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
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;

class NotInMaintenanceModeHandler extends ExtensionHandler
{
    private $kernel;

    public function __construct(Kernel $kernel)
    {
        $this->kernel = $kernel;
    }

    public function supports(object $extension) : bool
    {
        return $extension instanceof NotInMaintenanceMode;
    }

    /**
     * @param NotInMaintenanceMode $extension
     */
    public function filterSchedule(ScheduleRunContext $context, object $extension): void
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

**NOTE:** This is an example to show creating/registering a custom extension. In
a real world application, all that would be needed to accomplish the above
example would be the following in your `Kernel`:

```php
// src/Kernel.php

use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

class Kernel extends BaseKernel implements ScheduleBuilder
{
    public function isInMaintenanceMode(): bool
    {
        // return true if in maintenance mode...
    }

    public function buildSchedule(Schedule $schedule): void
    {
        $schedule->skip('Does not run in maintenance mode.', $this->isInMaintenanceMode());
    }

    // ...
}
```

## Events

The following Symfony events are available:

| Event                                                         | Description                      |
| ------------------------------------------------------------- | -------------------------------- |
| [`BeforeScheduleEvent`](../src/Event/BeforeScheduleEvent.php) | Runs before the schedule runs    |
| [`AfterScheduleEvent`](../src/Event/AfterScheduleEvent.php)   | Runs after the schedule runs     |
| [`BeforeTaskEvent`](../src/Event/BeforeTaskEvent.php)         | Runs before a due task runs      |
| [`AfterTaskEvent`](../src/Event/AfterTaskEvent.php)           | Runs after a due task runs       |
| [`BuildScheduleEvent`](../src/Event/BuildScheduleEvent.php)   | Define/manipulate tasks/schedule |

### Example: Add "withoutOverlapping" to all defined tasks

Let's configure all our tasks to have the [withoutOverlapping](define-tasks.md#prevent-overlap)
extension added.

The *subscriber* service:

```php
// src/EventSubscriber/ScheduleWithoutOverlappingSubscriber.php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\BuildScheduleEvent;

class ScheduleWithoutOverlappingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BuildScheduleEvent::class => [
                'onBuildSchedule',
                /*
                 * The actual building of the schedule happens at priority "0".
                 * We set to a lower priority to ensure all tasks have been defined.
                 */
                -100,
            ],
        ];
    }

    public function onBuildSchedule(BuildScheduleEvent $event): void
    {
        foreach ($event->getSchedule()->all() as $task) {
            $task->withoutOverlapping();
        }
    }
}
```

**NOTE:** If *autoconfiguration* is not enabled, add the `kernel.event_subscriber`
tag to the service.
