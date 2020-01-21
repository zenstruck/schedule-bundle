<?php

namespace Zenstruck\ScheduleBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
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
            ->addArgument('id', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'Task ID\'s to force run (if empty, the currently due tasks are run)')
            ->setHelp(<<<EOF
If no arguments are passed, all the tasks currently due are run. Pass one or
more Task ID's to "force" run these even if they are not due (only these are
run).

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

        return ($this->scheduleRunner)(...$input->getArgument('id'))->isSuccessful() ? 0 : 1;
    }
}
