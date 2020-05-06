<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Symfony\Component\Process\Process;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProcessTask extends Task
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

    public function getProcess(): Process
    {
        return $this->process;
    }

    public function getContext(): array
    {
        return [
            'Command Line' => $this->process->getCommandLine(),
            'Command Timeout' => $this->process->getTimeout(),
        ];
    }
}
