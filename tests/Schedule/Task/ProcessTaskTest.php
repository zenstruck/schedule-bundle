<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Task\ProcessTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProcessTaskTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_successful_result()
    {
        $result = (new ProcessTask('$(which php) -v'))->timeout(1)->cwd(__DIR__)();

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
}
