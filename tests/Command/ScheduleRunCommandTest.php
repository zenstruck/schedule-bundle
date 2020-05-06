<?php

namespace Zenstruck\ScheduleBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Zenstruck\ScheduleBundle\Command\ScheduleRunCommand;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\CallbackHandler;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleRunCommandTest extends TestCase
{
    /**
     * @test
     */
    public function no_tasks_defined()
    {
        $dispatcher = new EventDispatcher();
        $runner = (new MockScheduleBuilder())->getRunner($dispatcher);
        $commandTester = new CommandTester(new ScheduleRunCommand($runner, $dispatcher));

        $exit = $commandTester->execute([]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('No tasks due to run. (0 total tasks)', $commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function skipped_schedule()
    {
        $dispatcher = new EventDispatcher();
        $runner = (new MockScheduleBuilder())
            ->addTask(MockTask::success('my task 1'))
            ->addBuilder(new class() implements ScheduleBuilder {
                public function buildSchedule(Schedule $schedule): void
                {
                    $schedule->skip('This schedule was skipped.', true);
                }
            })
            ->getRunner($dispatcher)
        ;
        $commandTester = new CommandTester(new ScheduleRunCommand($runner, $dispatcher));

        $exit = $commandTester->execute([]);

        $this->assertSame(0, $exit);
        $this->assertStringNotContainsString('my task 1', $commandTester->getDisplay());
        $this->assertStringContainsString('Running 1 due task. (1 total tasks)', $commandTester->getDisplay());
        $this->assertStringContainsString('This schedule was skipped.', $commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function skipped_task()
    {
        $dispatcher = new EventDispatcher();
        $runner = (new MockScheduleBuilder())
            ->addTask(MockTask::skipped('this task skipped', 'my task 1'))
            ->addTask(MockTask::success('my task 2'))
            ->getRunner($dispatcher)
        ;
        $commandTester = new CommandTester(new ScheduleRunCommand($runner, $dispatcher));

        $exit = $commandTester->execute([]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Running 2 due tasks. (2 total tasks)', $commandTester->getDisplay());
        $this->assertStringContainsString('1/2 tasks ran, 1 succeeded, 1 skipped.', $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 1\n Skipped: this task skipped", $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 2\n Success.", $commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function successful_task()
    {
        $dispatcher = new EventDispatcher();
        $runner = (new MockScheduleBuilder())
            ->addTask(MockTask::success('my task 1'))
            ->addTask(MockTask::success('my task 2', 'task 2 output'))
            ->getRunner($dispatcher)
        ;
        $commandTester = new CommandTester(new ScheduleRunCommand($runner, $dispatcher));

        $exit = $commandTester->execute([]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Running 2 due tasks. (2 total tasks)', $commandTester->getDisplay());
        $this->assertStringContainsString('2/2 tasks ran, 2 succeeded.', $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 1\n Success", $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 2\n Success.", $commandTester->getDisplay());
        $this->assertStringNotContainsString('task 2 output', $commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function successful_task_verbose()
    {
        $dispatcher = new EventDispatcher();
        $runner = (new MockScheduleBuilder())
            ->addTask(MockTask::success('my task 1'))
            ->addTask(MockTask::success('my task 2', 'task 2 output'))
            ->getRunner($dispatcher)
        ;
        $commandTester = new CommandTester(new ScheduleRunCommand($runner, $dispatcher));

        $exit = $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Running 2 due tasks. (2 total tasks)', $commandTester->getDisplay());
        $this->assertStringContainsString('2/2 tasks ran, 2 succeeded.', $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 1\n Success", $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 2\n ---begin output---\ntask 2 output\n ---end output---\n Success.", $commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function failed_task()
    {
        $dispatcher = new EventDispatcher();
        $runner = (new MockScheduleBuilder())
            ->addTask(MockTask::failure('task 1 failure', 'my task 1', 'task 1 output'))
            ->addTask(MockTask::success('my task 2'))
            ->getRunner($dispatcher)
        ;
        $commandTester = new CommandTester(new ScheduleRunCommand($runner, $dispatcher));

        $exit = $commandTester->execute([]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Running 2 due tasks. (2 total tasks)', $commandTester->getDisplay());
        $this->assertStringContainsString('2/2 tasks ran, 1 succeeded, 1 failed.', $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 1\n Failure: task 1 failure", $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 2\n Success.", $commandTester->getDisplay());
        $this->assertStringNotContainsString('task 1 output', $commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function failed_task_verbose()
    {
        $dispatcher = new EventDispatcher();
        $runner = (new MockScheduleBuilder())
            ->addTask(MockTask::failure('task 1 failure', 'my task 1', 'task 1 output'))
            ->addTask(MockTask::success('my task 2'))
            ->getRunner($dispatcher)
        ;
        $commandTester = new CommandTester(new ScheduleRunCommand($runner, $dispatcher));

        $exit = $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Running 2 due tasks. (2 total tasks)', $commandTester->getDisplay());
        $this->assertStringContainsString('2/2 tasks ran, 1 succeeded, 1 failed.', $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 1\n ---begin output---\ntask 1 output\n ---end output---\n Failure: task 1 failure", $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 2\n Success.", $commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function failed_task_via_exception()
    {
        $dispatcher = new EventDispatcher();
        $runner = (new MockScheduleBuilder())
            ->addTask(MockTask::exception(new \RuntimeException('task 1 exception message'), 'my task 1', 'task 1 output'))
            ->addTask(MockTask::success('my task 2'))
            ->getRunner($dispatcher)
        ;
        $commandTester = new CommandTester(new ScheduleRunCommand($runner, $dispatcher));

        $exit = $commandTester->execute([]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Running 2 due tasks. (2 total tasks)', $commandTester->getDisplay());
        $this->assertStringContainsString('2/2 tasks ran, 1 succeeded, 1 failed.', $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 1\n Exception: RuntimeException: task 1 exception message", $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 2\n Success.", $commandTester->getDisplay());
        $this->assertStringNotContainsString('task 1 output', $commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function failed_task_via_exception_verbose()
    {
        $dispatcher = new EventDispatcher();
        $runner = (new MockScheduleBuilder())
            ->addTask(MockTask::exception(new \RuntimeException('task 1 exception message'), 'my task 1', 'task 1 output'))
            ->addTask(MockTask::success('my task 2'))
            ->getRunner($dispatcher)
        ;
        $commandTester = new CommandTester(new ScheduleRunCommand($runner, $dispatcher));

        $exit = $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Running 2 due tasks. (2 total tasks)', $commandTester->getDisplay());
        $this->assertStringContainsString('2/2 tasks ran, 1 succeeded, 1 failed.', $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 1\n ---begin output---\ntask 1 output\n ---end output---\n Exception: RuntimeException: task 1 exception message", $commandTester->getDisplay());
        $this->assertStringContainsString("Running MockTask: my task 2\n Success.", $commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function can_force_run_tasks()
    {
        $dispatcher = new EventDispatcher();
        $runner = (new MockScheduleBuilder())
            ->addTask(MockTask::success('my task 1'))
            ->addTask($task2 = MockTask::success('my task 2')->cron('@yearly'))
            ->addTask($task3 = MockTask::success('my task 3')->cron('@yearly'))
            ->getRunner($dispatcher)
        ;
        $commandTester = new CommandTester(new ScheduleRunCommand($runner, $dispatcher));

        $exit = $commandTester->execute([
            'id' => [$task2->getId(), $task3->getId()],
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Force Running 2 tasks. (3 total tasks)', $commandTester->getDisplay());
        $this->assertStringContainsString('2/2 tasks ran, 2 succeeded.', $commandTester->getDisplay());
        $this->assertStringContainsString("Force Running MockTask: my task 2\n Success", $commandTester->getDisplay());
        $this->assertStringContainsString("Force Running MockTask: my task 3\n Success.", $commandTester->getDisplay());
        $this->assertStringNotContainsString("MockTask: my task 1\n Success.", $commandTester->getDisplay());
    }

    /**
     * @test
     */
    public function force_running_an_invalid_task_throws_exception()
    {
        $dispatcher = new EventDispatcher();
        $runner = (new MockScheduleBuilder())
            ->addTask(MockTask::success('my task 1'))
            ->getRunner($dispatcher)
        ;
        $commandTester = new CommandTester(new ScheduleRunCommand($runner, $dispatcher));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Task with ID "invalid-id" not found.');

        $commandTester->execute(['id' => ['invalid-id']]);
    }

    /**
     * @test
     */
    public function force_running_a_task_with_a_duplicate_id_throws_exception()
    {
        $dispatcher = new EventDispatcher();
        $runner = (new MockScheduleBuilder())
            ->addTask($task = MockTask::success('my task'))
            ->addTask(MockTask::success('my task'))
            ->getRunner($dispatcher)
        ;
        $commandTester = new CommandTester(new ScheduleRunCommand($runner, $dispatcher));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Task ID \"{$task->getId()}\" is ambiguous, there are 2 tasks this id.");

        $commandTester->execute(['id' => [$task->getId()]]);
    }
}
