<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Extension;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class SelfHandlingExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function success_hooks_are_called()
    {
        $builder = $this->createBuilder(MockTask::success());

        (new MockScheduleBuilder())
            ->addBuilder($builder)
            ->run()
        ;

        $this->assertSame([
            'scheduleFilter',
            'scheduleBefore',
            'taskFilter',
            'taskBefore',
            'taskAfter',
            'taskSuccess',
            'scheduleAfter',
            'scheduleSuccess',
        ], $builder->calls);
    }

    /**
     * @test
     */
    public function failure_hooks_are_called()
    {
        $builder = $this->createBuilder(MockTask::exception(new \Exception()));

        (new MockScheduleBuilder())
            ->addBuilder($builder)
            ->run()
        ;

        $this->assertSame([
            'scheduleFilter',
            'scheduleBefore',
            'taskFilter',
            'taskBefore',
            'taskAfter',
            'taskFailure',
            'scheduleAfter',
            'scheduleFailure',
        ], $builder->calls);
    }

    private function createBuilder(Task $task): ScheduleBuilder
    {
        return new class($task) implements ScheduleBuilder {
            public $calls = [];

            private $task;

            public function __construct(Task $task)
            {
                $this->task = $task;
            }

            public function buildSchedule(Schedule $schedule): void
            {
                $schedule
                    ->filter(function () { $this->calls[] = 'scheduleFilter'; })
                    ->before(function () { $this->calls[] = 'scheduleBefore'; })
                    ->after(function () { $this->calls[] = 'scheduleAfter'; })
                    ->onSuccess(function () { $this->calls[] = 'scheduleSuccess'; })
                    ->onFailure(function () { $this->calls[] = 'scheduleFailure'; })
                ;

                $schedule->add($this->task)
                    ->filter(function () { $this->calls[] = 'taskFilter'; })
                    ->before(function () { $this->calls[] = 'taskBefore'; })
                    ->after(function () { $this->calls[] = 'taskAfter'; })
                    ->onSuccess(function () { $this->calls[] = 'taskSuccess'; })
                    ->onFailure(function () { $this->calls[] = 'taskFailure'; })
                ;
            }
        };
    }
}
