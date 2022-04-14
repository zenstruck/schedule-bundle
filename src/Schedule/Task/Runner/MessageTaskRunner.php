<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task\Runner;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\MessageTask;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @experimental This is experimental and may experience BC breaks
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MessageTaskRunner implements TaskRunner
{
    /** @var MessageBusInterface */
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
        $envelope = $this->bus->dispatch($task->getMessage(), $task->getStamps());
        $output = $this->handlerOutput($envelope);

        if (empty($output)) {
            return Result::failure($task, 'Message not handled or sent to transport.');
        }

        return Result::successful($task, \implode("\n", $output));
    }

    public function supports(Task $task): bool
    {
        return $task instanceof MessageTask;
    }

    /**
     * @return string[]
     */
    private function handlerOutput(Envelope $envelope): array
    {
        $output = [];

        foreach ($envelope->all(HandledStamp::class) as $stamp) {
            /** @var HandledStamp $stamp */
            $output[] = \sprintf('Handled by: "%s", return: %s', $stamp->getHandlerName(), $this->handledStampReturn($stamp));
        }

        foreach ($envelope->all(SentStamp::class) as $stamp) {
            /** @var SentStamp $stamp */
            $output[] = \sprintf('Sent to: "%s"', $stamp->getSenderClass());
        }

        return $output;
    }

    private function handledStampReturn(HandledStamp $stamp): string
    {
        $result = $stamp->getResult();

        switch (true) {
            case null === $result:
                return '(none)';

            case \is_scalar($result):
                return \sprintf('(%s) "%s"', \get_debug_type($result), $result);
        }

        return \sprintf('(%s)', \get_debug_type($result));
    }
}
