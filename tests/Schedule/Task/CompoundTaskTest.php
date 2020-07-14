<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Task\CompoundTask;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CompoundTaskTest extends TestCase
{
    /**
     * @test
     */
    public function cannot_nest_compound_tasks()
    {
        $task = new CompoundTask();

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Cannot nest compound tasks.');

        $task->add(new CompoundTask());
    }

    /**
     * @test
     */
    public function config_is_passed_to_sub_tasks(): void
    {
        $task = new CompoundTask();
        $task->add(new MockTask('subtask1'));
        $task->add(new MockTask('subtask2'));
        $task->config()->set('foo', 'bar');
        $task->config()->set('bar', 'foo');

        [$subtask1, $subtask2] = \iterator_to_array($task);

        $this->assertSame('bar', $subtask1->config()->get('foo'));
        $this->assertSame('foo', $subtask1->config()->get('bar'));
        $this->assertSame('bar', $subtask2->config()->get('foo'));
        $this->assertSame('foo', $subtask2->config()->get('bar'));
    }

    /**
     * @test
     */
    public function config_on_sub_tasks_takes_precedence_over_compound_task(): void
    {
        $subTask = new MockTask('subtask');
        $subTask->config()->set('key2', 'subtask value2');
        $task = new CompoundTask();
        $task->config()->set('key1', 'compound value1');
        $task->config()->set('key2', 'compound value2');
        $task->add($subTask);

        [$subTask] = \iterator_to_array($task);

        $this->assertSame('compound value1', $subTask->config()->get('key1'));
        $this->assertSame('subtask value2', $subTask->config()->get('key2'));
    }
}
