<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use Zenstruck\ScheduleBundle\Schedule\Task\ProcessTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProcessTaskTest extends TestCase
{
    /**
     * @test
     */
    public function has_default_description()
    {
        $this->assertSame('$(which php) -v', (new ProcessTask('$(which php) -v'))->getDescription());
        $this->assertSame('$(which php) -v', (new ProcessTask(Process::fromShellCommandline('$(which php) -v')))->getDescription());
    }

    /**
     * @test
     */
    public function task_has_context()
    {
        $task = new ProcessTask('/foo/bar');
        $this->assertSame(['Command Line' => '/foo/bar', 'Command Timeout' => '60'], $task->getContext());

        $task = new ProcessTask(Process::fromShellCommandline('/foo/bar')->setTimeout(30));
        $this->assertSame(['Command Line' => '/foo/bar', 'Command Timeout' => '30'], $task->getContext());
    }
}
