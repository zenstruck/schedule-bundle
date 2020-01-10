<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Symfony\Component\Process\Process;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProcessTask extends Task implements SelfRunningTask
{
    private $process;

    /**
     * @param string|Process $process
     */
    public function __construct($process)
    {
        if (!\class_exists(Process::class)) {
            throw new \LogicException(\sprintf('"symfony/process" is required to use "%s". Install with "composer require symfony/process".', self::class));
        }

        if (!$process instanceof Process) {
            $process = Process::fromShellCommandline($process);
        }

        $this->process = $process;

        parent::__construct($process->getCommandLine());
    }

    public function __invoke(): Result
    {
        $this->process->run();

        if ($this->process->isSuccessful()) {
            return Result::successful($this, $this->process->getOutput());
        }

        return Result::failure(
            $this,
            "Exit {$this->process->getExitCode()}: {$this->process->getExitCodeText()}",
            $this->process->getErrorOutput()
        );
    }
}
