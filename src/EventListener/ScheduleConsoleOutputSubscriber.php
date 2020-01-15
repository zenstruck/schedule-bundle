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
        if ($event->isSkipped()) {
            $this->io->note($event->getSkipReason());

            return;
        }

        $total = \count($event->getResults());
        $successful = \count($event->getSuccessful());
        $failures = \count($event->getFailures());
        $skipped = \count($event->getSkipped());
        $run = \count($event->getRun());
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
        $messages .= " (Duration: {$event->getFormattedDuration()}, Memory: {$event->getFormattedMemory()})";

        $this->io->{$event->isSuccessful() ? 'success' : 'error'}($messages);
    }

    public function beforeSchedule(BeforeScheduleEvent $event): void
    {
        $dueTaskCount = \count($event->getSchedule()->due());

        if (0 === $dueTaskCount) {
            $this->io->note(\sprintf('No tasks due to run. (%s total tasks)', \count($event->getSchedule()->all())));

            return;
        }

        $this->io->comment(\sprintf(
            'Running <info>%s</info> due task%s. (%s total tasks)',
            $dueTaskCount,
            $dueTaskCount > 1 ? 's' : '',
            \count($event->getSchedule()->all())
        ));
    }

    public function beforeTask(BeforeTaskEvent $event): void
    {
        $this->io->text("<comment>Running {$event->getTask()->getType()}:</comment> {$event->getTask()}");
    }

    public function afterTask(AfterTaskEvent $event): void
    {
        if ($this->io->isVerbose() && $output = $event->getResult()->getOutput()) {
            $this->io->text('---begin output---');
            $this->io->write($output);
            $this->io->text('---end output---');
        }

        $this->io->text(\sprintf('%s (<comment>Duration:</comment> %s, <comment>Memory:</comment> %s)',
            $this->afterTaskMessage($event->getResult()),
            $event->getFormattedDuration(),
            $event->getFormattedMemory()
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
