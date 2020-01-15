<?php

namespace Zenstruck\ScheduleBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Zenstruck\ScheduleBundle\EventListener\ScheduleConsoleOutputSubscriber;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleRunCommand extends Command
{
    protected static $defaultName = 'schedule:run';

    private $scheduleRunner;
    private $dispatcher;

    public function __construct(ScheduleRunner $scheduleRunner, EventDispatcherInterface $dispatcher)
    {
        $this->scheduleRunner = $scheduleRunner;
        $this->dispatcher = $dispatcher;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Runs scheduled tasks that are due')
            ->setHelp(<<<EOF
Exit code 0: no tasks ran, schedule skipped, or all tasks run were successful.
Exit code 1: one or more tasks failed.

Add this command as a Cron job to your production server(s) running every minute:

* * * * * cd /path-to-your-project && bin/console schedule:run >> /dev/null 2>&1
EOF
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $this->dispatcher->addSubscriber(new ScheduleConsoleOutputSubscriber($io));

        return ($this->scheduleRunner)()->isSuccessful() ? 0 : 1;
    }
}
