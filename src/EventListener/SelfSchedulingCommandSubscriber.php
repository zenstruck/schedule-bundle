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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\BuildScheduleEvent;
use Zenstruck\ScheduleBundle\Schedule\SelfSchedulingCommand;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SelfSchedulingCommandSubscriber implements EventSubscriberInterface
{
    /** @var iterable<SelfSchedulingCommand> */
    private $commands;

    /**
     * @param iterable<SelfSchedulingCommand> $commands
     */
    public function __construct(iterable $commands)
    {
        $this->commands = $commands;
    }

    public static function getSubscribedEvents(): array
    {
        return [BuildScheduleEvent::class => 'build'];
    }

    public function build(BuildScheduleEvent $event): void
    {
        foreach ($this->commands as $command) {
            if (!$command instanceof Command) {
                throw new \InvalidArgumentException(\sprintf('"%s" is not a console command. "%s" can only be used on commands.', $command::class, SelfSchedulingCommand::class));
            }

            $task = new CommandTask((string) $command->getName());

            $command->schedule($task);

            $event->getSchedule()->add($task);
        }
    }
}
