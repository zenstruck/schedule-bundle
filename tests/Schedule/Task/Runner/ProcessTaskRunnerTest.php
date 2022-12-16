<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task\Runner;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Task\ProcessTask;
use Zenstruck\ScheduleBundle\Schedule\Task\Runner\ProcessTaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ProcessTaskRunnerTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_successful_result()
    {
        $result = (new ProcessTaskRunner())(new ProcessTask('$(which php) -v'));

        $this->assertTrue($result->isSuccessful());
        $this->assertStringContainsString('PHP', $result->getOutput());
        $this->assertStringContainsString(\PHP_VERSION, $result->getOutput());
    }

    /**
     * @test
     */
    public function can_create_failed_result()
    {
        $result = (new ProcessTaskRunner())(new ProcessTask('sdfsdfsdf'));

        $this->assertTrue($result->isFailure());
        $this->assertSame('Exit 127: Command not found', $result->getDescription());
        $this->assertSame("sh: 1: sdfsdfsdf: not found\n", $result->getOutput());
    }

    /**
     * @test
     */
    public function failed_result_contains_non_error_output()
    {
        $result = (new ProcessTaskRunner())(new ProcessTask(\sprintf('$(which php) %s', __DIR__.'/../../../Fixture/script.sh')));

        $this->assertTrue($result->isFailure());
        $this->assertSame('Exit 1: General error', $result->getDescription());
        $this->assertSame("non-error output\nerror output\n", $result->getOutput());
    }
}
