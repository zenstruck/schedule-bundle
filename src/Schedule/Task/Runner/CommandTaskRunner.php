<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task\Runner;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Process;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CommandTaskRunner implements TaskRunner
{
    private $application;

    public function __construct(Application $application)
    {
        $this->application = $application;
    }

    /**
     * @param CommandTask|Task $task
     */
    public function __invoke(Task $task): Result
    {
        $output = new BufferedOutput();
        $this->application->setCatchExceptions(false);
        $this->application->setAutoExit(false);

        try {
            $exitCode = $this->application->run($task->createCommandInput($this->application), $output);
        } catch (\Throwable $e) {
            return Result::exception($task, $e, $output->fetch());
        }

        if (0 === $exitCode) {
            return Result::successful($task, $output->fetch());
        }

        return Result::failure($task, "Exit {$exitCode}: {$this->getFailureMessage($exitCode)}", $output->fetch());
    }

    public function supports(Task $task): bool
    {
        return $task instanceof CommandTask;
    }

    private function getFailureMessage(int $exitCode): string
    {
        if (\class_exists(Process::class) && isset(Process::$exitCodes[$exitCode])) {
            return Process::$exitCodes[$exitCode];
        }

        return 'Unknown error';
    }
}
