<?php

namespace Zenstruck\ScheduleBundle\EventListener;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\ScheduleBuildEvent;
use Zenstruck\ScheduleBundle\Schedule\SelfSchedulingCommand;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SelfSchedulingSubscriber implements EventSubscriberInterface
{
    private $commands;

    /**
     * @param \Zenstruck\ScheduleBundle\Schedule\SelfSchedulingCommand[] $commands
     */
    public function __construct(iterable $commands)
    {
        $this->commands = $commands;
    }

    public static function getSubscribedEvents(): array
    {
        return [ScheduleBuildEvent::class => 'build'];
    }

    public function build(ScheduleBuildEvent $event): void
    {
        foreach ($this->commands as $command) {
            if (!$command instanceof Command) {
                throw new \InvalidArgumentException(\sprintf('"%s" is not a console command. "%s" can only be used on commands.', \get_class($command), SelfSchedulingCommand::class));
            }

            $task = new CommandTask($command->getName());

            $command->schedule($task);

            $event->getSchedule()->add($task);
        }
    }
}
