<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Symfony\Component\Process\Process;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProcessTask extends Task implements SelfRunningTask
{
    private $command;
    private $cwd;
    private $timeout = 60;

    public function __construct(string $command)
    {
        if (!\class_exists(Process::class)) {
            throw new \LogicException(\sprintf('"symfony/process" is required to use "%s". Install with "composer require symfony/process".', self::class));
        }

        parent::__construct($command);

        $this->command = $command;
    }

    public function __invoke(): Result
    {
        $process = Process::fromShellCommandline($this->command, $this->cwd)
            ->setTimeout($this->timeout)
        ;

        $process->run();

        if ($process->isSuccessful()) {
            return Result::successful($this, $process->getOutput());
        }

        return Result::failure(
            $this,
            "Exit {$process->getExitCode()}: {$process->getExitCodeText()}",
            $process->getErrorOutput()
        );
    }

    public function cwd(string $cwd): self
    {
        $this->cwd = $cwd;

        return $this;
    }

    public function timeout(?float $seconds): self
    {
        $this->timeout = $seconds;

        return $this;
    }
}
