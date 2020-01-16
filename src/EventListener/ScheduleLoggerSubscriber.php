<?php

namespace Zenstruck\ScheduleBundle\EventListener;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\AfterScheduleEvent;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleLoggerSubscriber implements EventSubscriberInterface
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeScheduleEvent::class => 'beforeSchedule',
            AfterScheduleEvent::class => 'afterSchedule',
            BeforeTaskEvent::class => 'beforeTask',
            AfterTaskEvent::class => 'afterTask',
        ];
    }

    public function beforeSchedule(BeforeScheduleEvent $event): void
    {
        $dueTaskCount = \count($event->getSchedule()->due());

        if (0 === $dueTaskCount) {
            $this->logger->debug('No tasks due to run.', ['total' => \count($event->getSchedule()->all())]);

            return;
        }

        $this->logger->info(\sprintf('Running %s due task%s.', $dueTaskCount, $dueTaskCount > 1 ? 's' : ''), [
            'total' => \count($event->getSchedule()->all()),
            'due' => $dueTaskCount,
        ]);
    }

    public function afterSchedule(AfterScheduleEvent $event): void
    {
        if ($event->isSkipped()) {
            $this->logger->info($event->getSkipReason());
        }

        $total = \count($event->getResults());
        $successful = \count($event->getSuccessful());
        $failures = \count($event->getFailures());
        $skipped = \count($event->getSkipped());
        $run = \count($event->getRun());
        $level = $event->isSuccessful() ? LogLevel::INFO : LogLevel::ERROR;

        if (0 === $total) {
            return;
        }

        $this->logger->log($level, "{$run}/{$total} tasks ran", [
            'total' => $total,
            'successful' => $successful,
            'skipped' => $skipped,
            'failures' => $failures,
            'duration' => $event->getFormattedDuration(),
            'memory' => $event->getFormattedMemory(),
        ]);
    }

    public function beforeTask(BeforeTaskEvent $event): void
    {
        $this->logger->info("Running \"{$event->getTask()->getType()}\": {$event->getTask()}");
    }

    public function afterTask(AfterTaskEvent $event): void
    {
        $result = $event->getResult();
        $task = $result->getTask();

        if ($result->isSkipped()) {
            $this->logger->info("Skipped \"{$task->getType()}\": {$task}");

            return;
        }

        $context = [
            'duration' => $event->getFormattedDuration(),
            'memory' => $event->getFormattedMemory(),
            'task' => $task,
            'result' => $result,
        ];

        if ($result->isSuccessful()) {
            $this->logger->info("Successfully ran \"{$task->getType()}\": {$task}", $context);

            return;
        }

        $context['output'] = $result->getOutput();

        if (!$result->isException()) {
            $this->logger->error("Failure when running \"{$task->getType()}\": {$task}", $context);

            return;
        }

        $context['exception'] = $result->getException();

        $this->logger->critical("Exception thrown when running \"{$task->getType()}\": {$task}", $context);
    }
}
