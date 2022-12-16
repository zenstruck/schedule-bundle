<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ResultTest extends TestCase
{
    /**
     * @test
     */
    public function can_create_skipped()
    {
        $task = new MockTask();
        $result = Result::skipped($task, 'some reason');

        $this->assertSame($task, $result->getTask());
        $this->assertTrue($result->isSkipped());
        $this->assertFalse($result->isSuccessful());
        $this->assertFalse($result->isFailure());
        $this->assertFalse($result->isException());
        $this->assertFalse($result->hasRun());
        $this->assertNull($result->getException());
        $this->assertNull($result->getOutput());
        $this->assertSame('some reason', $result->getDescription());
        $this->assertSame('some reason', (string) $result);
        $this->assertSame(Result::SKIPPED, $result->getType());
    }

    /**
     * @test
     */
    public function can_create_successful()
    {
        $task = new MockTask();
        $result = Result::successful($task);

        $this->assertSame($task, $result->getTask());
        $this->assertFalse($result->isSkipped());
        $this->assertTrue($result->isSuccessful());
        $this->assertFalse($result->isFailure());
        $this->assertFalse($result->isException());
        $this->assertTrue($result->hasRun());
        $this->assertNull($result->getException());
        $this->assertNull($result->getOutput());
        $this->assertSame('Successful', $result->getDescription());
        $this->assertSame('Successful', (string) $result);
        $this->assertSame(Result::SUCCESSFUL, $result->getType());
    }

    /**
     * @test
     */
    public function can_create_failure()
    {
        $task = new MockTask();
        $result = Result::failure($task, 'some reason', 'some output');

        $this->assertSame($task, $result->getTask());
        $this->assertFalse($result->isSkipped());
        $this->assertFalse($result->isSuccessful());
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isException());
        $this->assertTrue($result->hasRun());
        $this->assertNull($result->getException());
        $this->assertSame('some output', $result->getOutput());
        $this->assertSame('some reason', $result->getDescription());
        $this->assertSame('some reason', (string) $result);
        $this->assertSame(Result::FAILED, $result->getType());
    }

    /**
     * @test
     */
    public function can_create_exception()
    {
        $task = new MockTask();
        $exception = new \RuntimeException('exception message');
        $result = Result::exception($task, $exception, 'some output');

        $this->assertSame($task, $result->getTask());
        $this->assertFalse($result->isSkipped());
        $this->assertFalse($result->isSuccessful());
        $this->assertTrue($result->isFailure());
        $this->assertTrue($result->isException());
        $this->assertTrue($result->hasRun());
        $this->assertSame($exception, $result->getException());
        $this->assertSame('some output', $result->getOutput());
        $this->assertSame('RuntimeException: exception message', $result->getDescription());
        $this->assertSame('RuntimeException: exception message', (string) $result);
        $this->assertSame(Result::FAILED, $result->getType());
    }
}
