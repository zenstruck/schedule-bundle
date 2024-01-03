<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    /** @var LoggerInterface */
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

        $allTaskCount = \count($context->getSchedule()->all());
        $dueTaskCount = \count($context->dueTasks());

        if (0 === $dueTaskCount) {
            $this->logger->debug('No tasks due to run.', ['total' => $allTaskCount]);

            return;
        }

        $message = \sprintf('%s %d %stask%s.',
            $context->isForceRun() ? 'Force running' : 'Running',
            $dueTaskCount,
            $context->isForceRun() ? '' : 'due ',
            $dueTaskCount > 1 ? 's' : '',
        );

        $this->logger->info($message, [
            'total' => $allTaskCount,
            'due' => $dueTaskCount,
        ]);
    }

    public function afterSchedule(AfterScheduleEvent $event): void
    {
        $context = $event->runContext();

        if ($context->isSkipped()) {
            $this->logger->info($context->getSkipReason());

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
            'forced' => $context->isForceRun(),
        ]);
    }

    public function beforeTask(BeforeTaskEvent $event): void
    {
        $context = $event->runContext();
        $task = $context->getTask();

        $this->logger->info(\sprintf('%s "%s"',
            $context->getScheduleRunContext()->isForceRun() ? 'Force running' : 'Running',
            $task,
        ), ['id' => $task->getId()]);
    }

    public function afterTask(AfterTaskEvent $event): void
    {
        $context = $event->runContext();

        $result = $context->getResult();
        $task = $result->getTask();
        $logContext = ['id' => $task->getId()];

        if ($result->isSkipped()) {
            $this->logger->info("Skipped \"{$task}\" ({$result->getDescription()})", $logContext);

            return;
        }

        $logContext['result'] = $result->getDescription();
        $logContext['duration'] = $context->getFormattedDuration();
        $logContext['memory'] = $context->getFormattedMemory();
        $logContext['forced'] = $context->getScheduleRunContext()->isForceRun();

        if ($result->isSuccessful()) {
            $this->logger->info("Successfully ran \"{$task}\"", $logContext);

            return;
        }

        if ($result->getOutput()) {
            $logContext['output'] = $result->getOutput();
        }

        if (!$result->isException()) {
            $this->logger->error("Failure when running \"{$task}\"", $logContext);

            return;
        }

        $logContext['exception'] = $result->getException();

        $this->logger->critical("Exception thrown when running \"{$task}\"", $logContext);
    }
}
