<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Extension;

use PHPUnit\Framework\TestCase;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\PingHandler;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PingExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function success_webhooks_are_pinged()
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->exactly(6))->method('request')->withConsecutive(
            [$this->equalTo('GET'), $this->equalTo('schedule-before.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('task-before.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('task-after.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('task-success.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('schedule-after.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('schedule-success.com'), $this->isType('array')]
        );

        (new MockScheduleBuilder())
            ->addHandler(new PingHandler($client))
            ->addBuilder($this->createBuilder(MockTask::success()))
            ->run()
        ;
    }

    /**
     * @test
     */
    public function failure_webhooks_are_pinged()
    {
        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->exactly(6))->method('request')->withConsecutive(
            [$this->equalTo('GET'), $this->equalTo('schedule-before.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('task-before.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('task-after.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('task-failure.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('schedule-after.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('schedule-failure.com'), $this->isType('array')]
        );

        (new MockScheduleBuilder())
            ->addHandler(new PingHandler($client))
            ->addBuilder($this->createBuilder(MockTask::exception(new \Exception())))
            ->run()
        ;
    }

    private function createBuilder(Task $task): ScheduleBuilder
    {
        return new class($task) implements ScheduleBuilder {
            private $task;

            public function __construct(Task $task)
            {
                $this->task = $task;
            }

            public function buildSchedule(Schedule $schedule): void
            {
                $schedule
                    ->pingBefore('schedule-before.com')
                    ->pingAfter('schedule-after.com')
                    ->pingOnSuccess('schedule-success.com')
                    ->pingOnFailure('schedule-failure.com')
                ;

                $schedule->add($this->task)
                    ->pingBefore('task-before.com')
                    ->pingAfter('task-after.com')
                    ->pingOnSuccess('task-success.com')
                    ->pingOnFailure('task-failure.com')
                ;
            }
        };
    }
}
