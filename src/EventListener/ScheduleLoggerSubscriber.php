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
        $context = $event->runContext();

        $allTaskCount = \count($context->schedule()->all());
        $dueTaskCount = \count($context->dueTasks());

        if (0 === $dueTaskCount) {
            $this->logger->debug('No tasks due to run.', ['total' => $allTaskCount]);

            return;
        }

        $this->logger->info(\sprintf('Running %s due task%s.', $dueTaskCount, $dueTaskCount > 1 ? 's' : ''), [
            'total' => $allTaskCount,
            'due' => $dueTaskCount,
        ]);
    }

    public function afterSchedule(AfterScheduleEvent $event): void
    {
        $context = $event->runContext();

        if ($context->isSkipped()) {
            $this->logger->info($context->skipReason());

            return;
        }

        $total = \count($context->getResults());
        $successful = \count($context->getSuccessful());
        $failures = \count($context->getFailures());
        $skipped = \count($context->getSkipped());
        $run = \count($context->getRun());
        $level = $context->isSuccessful() ? LogLevel::INFO : LogLevel::ERROR;

        if (0 === $total) {
            return;
        }

        $this->logger->log($level, "{$run}/{$total} tasks ran", [
            'total' => $total,
            'successful' => $successful,
            'skipped' => $skipped,
            'failures' => $failures,
            'duration' => $context->getFormattedDuration(),
            'memory' => $context->getFormattedMemory(),
        ]);
    }

    public function beforeTask(BeforeTaskEvent $event): void
    {
        $task = $event->runContext()->task();

        $this->logger->info("Running \"{$task->getType()}\": {$task}");
    }

    public function afterTask(AfterTaskEvent $event): void
    {
        $context = $event->runContext();

        $result = $context->result();
        $task = $result->getTask();

        if ($result->isSkipped()) {
            $this->logger->info("Skipped \"{$task->getType()}\": {$task}", ['reason' => $result->getDescription()]);

            return;
        }

        $logContext = [
            'duration' => $context->getFormattedDuration(),
            'memory' => $context->getFormattedMemory(),
            'task' => $task,
            'result' => $result,
        ];

        if ($result->isSuccessful()) {
            $this->logger->info("Successfully ran \"{$task->getType()}\": {$task}", $logContext);

            return;
        }

        $logContext['output'] = $result->getOutput();

        if (!$result->isException()) {
            $this->logger->error("Failure when running \"{$task->getType()}\": {$task}", $logContext);

            return;
        }

        $logContext['exception'] = $result->getException();

        $this->logger->critical("Exception thrown when running \"{$task->getType()}\": {$task}", $logContext);
    }
}
