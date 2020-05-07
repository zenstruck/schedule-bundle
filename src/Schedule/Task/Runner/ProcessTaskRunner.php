<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task\Runner;

use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\ProcessTask;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProcessTaskRunner implements TaskRunner
{
    /**
     * @param ProcessTask|Task $task
     */
    public function __invoke(Task $task): Result
    {
        $process = clone $task->getProcess();

        $process->run();

        if ($process->isSuccessful()) {
            return Result::successful($task, $process->getOutput());
        }

        return Result::failure(
            $task,
            "Exit {$process->getExitCode()}: {$process->getExitCodeText()}",
            $process->getErrorOutput()
        );
    }

    public function supports(Task $task): bool
    {
        return $task instanceof ProcessTask;
    }
}
