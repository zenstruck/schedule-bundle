<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Symfony\Component\Process\Process;
use Zenstruck\ScheduleBundle\Schedule\HasMissingDependencyMessage;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProcessTask extends Task implements HasMissingDependencyMessage
{
    /** @var Process */
    private $process;

    /**
     * @param string|Process $process
     */
    public function __construct($process)
    {
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
            'Command Timeout' => (string) $this->process->getTimeout(),
        ];
    }

    public static function getMissingDependencyMessage(): string
    {
        return \sprintf('"symfony/process" is required to use "%s". Install with "composer require symfony/process".', self::class);
    }
}
