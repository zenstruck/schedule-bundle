<?php

namespace Zenstruck\ScheduleBundle\EventListener;

use Symfony\Component\Console\Style\OutputStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\AfterScheduleEvent;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleConsoleOutputSubscriber implements EventSubscriberInterface
{
    private $io;

    public function __construct(OutputStyle $io)
    {
        $this->io = $io;
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

    public function afterSchedule(AfterScheduleEvent $event): void
    {
        $context = $event->runContext();

        if ($context->isSkipped()) {
            $this->io->note($context->skipReason());

            return;
        }

        $total = \count($context->getResults());
        $successful = \count($context->getSuccessful());
        $failures = \count($context->getFailures());
        $skipped = \count($context->getSkipped());
        $run = \count($context->getRun());
        $messages = ["{$run}/{$total} tasks ran"];

        if (0 === $total) {
            return;
        }

        if ($successful > 0) {
            $messages[] = "{$successful} succeeded";
        }

        if ($skipped > 0) {
            $messages[] = "{$skipped} skipped";
        }

        if ($failures > 0) {
            $messages[] = "{$failures} failed";
        }

        $messages = \implode(', ', $messages).'.';
        $messages .= " (Duration: {$context->getFormattedDuration()}, Memory: {$context->getFormattedMemory()})";

        $this->io->{$context->isSuccessful() ? 'success' : 'error'}($messages);
    }

    public function beforeSchedule(BeforeScheduleEvent $event): void
    {
        $context = $event->runContext();

        $allTaskCount = \count($context->schedule()->all());
        $dueTaskCount = \count($context->dueTasks());

        if (0 === $dueTaskCount) {
            $this->io->note(\sprintf('No tasks due to run. (%s total tasks)', $allTaskCount));

            return;
        }

        $this->io->comment(\sprintf(
            '%sRunning <info>%s</info> %stask%s. (%s total tasks)',
            $context->isForceRun() ? '<error>Force</error> ' : '',
            $dueTaskCount,
            $context->isForceRun() ? '' : 'due ',
            $dueTaskCount > 1 ? 's' : '',
            $allTaskCount
        ));
    }

    public function beforeTask(BeforeTaskEvent $event): void
    {
        $context = $event->runContext();
        $task = $context->task();

        $this->io->text(\sprintf(
            '%sRunning <comment>%s:</comment> %s',
            $context->scheduleRunContext()->isForceRun() ? '<error>Force</error> ' : '',
            $task->getType(),
            $task
        ));
    }

    public function afterTask(AfterTaskEvent $event): void
    {
        $context = $event->runContext();

        if ($this->io->isVerbose() && $output = $context->result()->getOutput()) {
            $this->io->text('---begin output---');
            $this->io->write($output);
            $this->io->text('---end output---');
        }

        $this->io->text(\sprintf('%s (<comment>Duration:</comment> %s, <comment>Memory:</comment> %s)',
            $this->afterTaskMessage($context->result()),
            $context->getFormattedDuration(),
            $context->getFormattedMemory()
        ));
        $this->io->newLine();
    }

    private function afterTaskMessage(Result $result): string
    {
        if ($result->isException()) {
            return "<error>Exception:</error> {$result->getDescription()}";
        }

        if ($result->isFailure()) {
            return "<error>Failure:</error> {$result->getDescription()}";
        }

        if ($result->isSkipped()) {
            return "<question>Skipped:</question> {$result->getDescription()}";
        }

        return '<info>Success.</info>';
    }
}
