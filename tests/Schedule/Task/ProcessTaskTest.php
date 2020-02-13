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
    public function can_create_successful_result()
    {
        $result = (new ProcessTask('$(which php) -v'))();

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('PHP', $result->getOutput());
        $this->assertStringContainsString(PHP_VERSION, $result->getOutput());
    }

    /**
     * @test
     */
    public function can_create_failed_result()
    {
        $result = (new ProcessTask('sdfsdfsdf'))();

        $this->assertTrue($result->isFailure());
        $this->assertSame('Exit 127: Command not found', $result->getDescription());
        $this->assertSame("sh: 1: sdfsdfsdf: not found\n", $result->getOutput());
    }

    /**
     * @test
     */
    public function can_add_a_process_instance()
    {
        $result = (new ProcessTask(Process::fromShellCommandline('$(which php) -v')))();

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('PHP', $result->getOutput());
        $this->assertStringContainsString(PHP_VERSION, $result->getOutput());
    }

    /**
     * @test
     */
    public function task_has_context()
    {
        $task = new ProcessTask('/foo/bar');
        $this->assertSame(['Command Line' => '/foo/bar', 'Command Timeout' => 60.0], $task->getContext());

        $task = new ProcessTask(Process::fromShellCommandline('/foo/bar')->setTimeout(30));
        $this->assertSame(['Command Line' => '/foo/bar', 'Command Timeout' => 30.0], $task->getContext());
    }
}
