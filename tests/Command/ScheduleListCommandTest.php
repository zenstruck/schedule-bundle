<?php

namespace Zenstruck\ScheduleBundle\Tests\Command;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\FormatterHelper;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\ScheduleBundle\Command\ScheduleListCommand;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandlerRegistry;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleListCommandTest extends TestCase
{
    /**
     * @test
     */
    public function no_tasks_defined()
    {
        $runner = (new MockScheduleBuilder())->getRunner();
        $commandTester = new CommandTester(new ScheduleListCommand($runner, new ExtensionHandlerRegistry([])));

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No scheduled tasks configured.');

        $commandTester->execute([]);
    }

    /**
     * @test
     */
    public function lists_configured_tasks_and_issues()
    {
        $runner = (new MockScheduleBuilder())
            ->addBuilder(new class() implements ScheduleBuilder {
                public function buildSchedule(Schedule $schedule): void
                {
                    $schedule->emailOnFailure('admin@example.com');
                    $schedule->addCommand('my:command')
                        ->mondays()
                        ->at('1:30')
                        ->emailOnFailure('admin@example.com')
                        ->pingOnFailure('https://example.com/my-command-failed')
                    ;
                }
            })
            ->getRunner()
        ;
        $command = new ScheduleListCommand($runner, new ExtensionHandlerRegistry([]));
        $command->setHelperSet(new HelperSet([new FormatterHelper()]));
        $command->setApplication(new Application());
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
        $output = $this->normalizeOutput($commandTester);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('[!] CommandTask my:command 2 30 1 * * 1 (Every Monday at 1:30am)', $output);
        $this->assertStringContainsString('[WARNING] 4 task issues:', $output);
        $this->assertStringContainsString('[ERROR] No task runner registered to handle "Zenstruck\ScheduleBundle\Schedule\Task\CommandTask".', $output);
        $this->assertStringContainsString('[ERROR] To use the email extension you must configure a mailer (config path: "zenstruck_schedule.mailer").', $output);
        $this->assertStringContainsString('[ERROR] Symfony HttpClient is required to use the ping extension. Install with "composer require symfony/http-client".', $output);
        $this->assertStringContainsString('[ERROR] Command "my:command" not registered.', $output);
        $this->assertStringContainsString('1 Schedule Extension:', $output);
        $this->assertStringContainsString('On Schedule Failure, email output to "admin@example.com"', $output);
        $this->assertStringContainsString('[WARNING] 1 issue with schedule:', $output);
        $this->assertStringNotContainsString('[OK] No schedule or task issues.', $output);
    }

    /**
     * @test
     */
    public function renders_exception_stack_trace_if_verbose()
    {
        $runner = (new MockScheduleBuilder())
            ->addBuilder(new class() implements ScheduleBuilder {
                public function buildSchedule(Schedule $schedule): void
                {
                    $schedule->addCommand('my:command')
                        ->mondays()
                        ->at('1:30')
                    ;
                }
            })
            ->getRunner()
        ;
        $command = new ScheduleListCommand($runner, new ExtensionHandlerRegistry([]));
        $command->setHelperSet(new HelperSet([new FormatterHelper()]));
        $command->setApplication(new Application());
        $commandTester = new CommandTester($command);

        $commandTester->execute([], ['verbosity' => OutputInterface::VERBOSITY_VERBOSE]);
        $output = $this->normalizeOutput($commandTester);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('[WARNING] 2 task issues:', $output);
        $this->assertStringContainsString('In ScheduleRunner.php line', $output);
        $this->assertStringContainsString('[LogicException]', $output);
        $this->assertStringContainsString('No task runner registered to handle', $output);
        $this->assertStringContainsString('In CommandTask.php line', $output);
        $this->assertStringContainsString('[Symfony\Component\Console\Exception\CommandNotFoundException]', $output);
        $this->assertStringContainsString('Command "my:command" not registered.', $output);
        $this->assertStringContainsString('Exception trace:', $output);
        $this->assertStringNotContainsString('[OK] No schedule or task issues.', $output);
    }

    /**
     * @test
     */
    public function lists_configured_tasks_and_issues_in_detail()
    {
        $runner = (new MockScheduleBuilder())
            ->addBuilder(new class() implements ScheduleBuilder {
                public function buildSchedule(Schedule $schedule): void
                {
                    $schedule->emailOnFailure('admin@example.com');
                    $schedule->addCommand('my:command')
                        ->arguments('arg1', '--option1')
                        ->mondays()
                        ->at('1:30')
                        ->emailOnFailure('admin@example.com')
                        ->pingOnFailure('https://example.com/my-command-failed')
                    ;
                }
            })
            ->getRunner()
        ;
        $command = new ScheduleListCommand($runner, new ExtensionHandlerRegistry([]));
        $command->setHelperSet(new HelperSet([new FormatterHelper()]));
        $command->setApplication(new Application());
        $commandTester = new CommandTester($command);

        $commandTester->execute(['--detail' => null]);
        $output = $this->normalizeOutput($commandTester);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('1 Scheduled Task Configured', $output);
        $this->assertStringContainsString('(1/1) CommandTask: my:command', $output);
        $this->assertStringContainsString('2d8aa4774f95e50c7408156fca071b017bf11030', $output, 'Shows task id');
        $this->assertStringContainsString(CommandTask::class, $output, 'Shows task id');
        $this->assertStringContainsString('30 1 * * 1 (Every Monday at 1:30am)', $output);
        $this->assertStringContainsString('Mon,', $output);
        $this->assertStringContainsString('Command Arguments', $output);
        $this->assertStringContainsString('arg1 --option1', $output);
        $this->assertStringContainsString('2 Task Extensions:', $output);
        $this->assertStringContainsString('On Task Failure, email output to "admin@example.com"', $output);
        $this->assertStringContainsString('On Task Failure, ping "https://example.com/my-command-failed"', $output);
        $this->assertStringContainsString('[WARNING] 4 issues with this task:', $output);
        $this->assertStringContainsString('[ERROR] No task runner registered to handle "Zenstruck\ScheduleBundle\Schedule\Task\CommandTask".', $output);
        $this->assertStringContainsString('[ERROR] To use the email extension you must configure a mailer (config path: "zenstruck_schedule.mailer").', $output);
        $this->assertStringContainsString('[ERROR] Symfony HttpClient is required to use the ping extension. Install with "composer require symfony/http-client".', $output);
        $this->assertStringContainsString('[ERROR] Command "my:command" not registered.', $output);
        $this->assertStringContainsString('1 Schedule Extension:', $output);
        $this->assertStringContainsString('On Schedule Failure, email output to "admin@example.com"', $output);
        $this->assertStringContainsString('[WARNING] 1 issue with schedule:', $output);
        $this->assertStringNotContainsString('[OK] No schedule or task issues.', $output);
    }

    /**
     * @test
     */
    public function command_task_with_invalid_argument_shows_as_error()
    {
        $runner = (new MockScheduleBuilder())
            ->addTask(new CommandTask('my:command -v --option1'))
            ->getRunner()
        ;

        $application = new Application();
        $application->add(new class() extends Command {
            protected static $defaultName = 'my:command';

            protected function configure()
            {
                $this->addArgument('arg1');
            }
        });
        $command = new ScheduleListCommand($runner, new ExtensionHandlerRegistry([]));
        $command->setHelperSet(new HelperSet([new FormatterHelper()]));
        $command->setApplication($application);
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
        $output = $this->normalizeOutput($commandTester);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('1 Scheduled Task Configured', $output);
        $this->assertStringContainsString('CommandTask my:command', $output);
        $this->assertStringContainsString('[WARNING] 2 task issues:', $output);
        $this->assertStringContainsString('[ERROR] The "--option1" option does not exist.', $output);
        $this->assertStringNotContainsString('[OK] No schedule or task issues.', $output);
    }

    /**
     * @test
     */
    public function no_issues_returns_successful_exit_code()
    {
        $runner = (new MockScheduleBuilder())
            ->addTask(new MockTask('my task'))
            ->getRunner()
        ;

        $command = new ScheduleListCommand($runner, new ExtensionHandlerRegistry([]));
        $command->setHelperSet(new HelperSet([new FormatterHelper()]));
        $command->setApplication(new Application());
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
        $output = $this->normalizeOutput($commandTester);

        $this->assertSame(0, $commandTester->getStatusCode());
        $this->assertStringContainsString('1 Scheduled Task Configured', $output);
        $this->assertStringContainsString('MockTask', $output);
        $this->assertStringContainsString('my task', $output);
        $this->assertStringContainsString('[OK] No schedule or task issues.', $output);
    }

    /**
     * @test
     */
    public function can_show_hashed_expressions()
    {
        $runner = (new MockScheduleBuilder())
            ->addTask((new MockTask('my task'))->cron('#daily'))
            ->getRunner()
        ;

        $command = new ScheduleListCommand($runner, new ExtensionHandlerRegistry([]));
        $command->setHelperSet(new HelperSet([new FormatterHelper()]));
        $command->setApplication(new Application());
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
        $output = $this->normalizeOutput($commandTester);

        $this->assertStringContainsString('56 20 * * * (Every day at 8:56pm)', $output);
        $this->assertStringNotContainsString('#daily', $output);

        $commandTester->execute(['--detail' => null]);
        $output = $this->normalizeOutput($commandTester);

        $this->assertStringContainsString('Calculated Frequency', $output);
        $this->assertStringContainsString('56 20 * * * (Every day at 8:56pm)', $output);
        $this->assertStringContainsString('Raw Frequency', $output);
        $this->assertStringContainsString('#daily', $output);
    }

    /**
     * @test
     */
    public function can_show_extended_expressions()
    {
        $runner = (new MockScheduleBuilder())
            ->addTask((new MockTask('my task'))->cron('@daily'))
            ->getRunner()
        ;

        $command = new ScheduleListCommand($runner, new ExtensionHandlerRegistry([]));
        $command->setHelperSet(new HelperSet([new FormatterHelper()]));
        $command->setApplication(new Application());
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
        $output = $this->normalizeOutput($commandTester);

        $this->assertStringContainsString('@daily', $output);
    }

    /**
     * @test
     */
    public function shows_full_task_id_in_detail()
    {
        $runner = (new MockScheduleBuilder())
            ->addTask($task = (new MockTask('my task'))->cron('@daily'))
            ->getRunner()
        ;

        $command = new ScheduleListCommand($runner, new ExtensionHandlerRegistry([]));
        $command->setHelperSet(new HelperSet([new FormatterHelper()]));
        $command->setApplication(new Application());
        $commandTester = new CommandTester($command);

        $commandTester->execute(['--detail' => null]);
        $output = $this->normalizeOutput($commandTester);

        $this->assertStringContainsString('ID', $output);
        $this->assertStringContainsString($task->getId(), $output);
    }

    /**
     * @test
     */
    public function shows_schedule_issue_for_duplicate_task_id()
    {
        $runner = (new MockScheduleBuilder())
            ->addTask(new MockTask('task1'))
            ->addTask(new MockTask('task2'))
            ->addTask(new MockTask('task2'))
            ->addTask(new MockTask('task3'))
            ->addTask(new MockTask('task3'))
            ->addTask(new MockTask('task3'))
            ->getRunner()
        ;

        $command = new ScheduleListCommand($runner, new ExtensionHandlerRegistry([]));
        $command->setHelperSet(new HelperSet([new FormatterHelper()]));
        $command->setApplication(new Application());
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);
        $output = $this->normalizeOutput($commandTester);

        $this->assertSame(1, $commandTester->getStatusCode());
        $this->assertStringContainsString('[WARNING] 2 issues with schedule:', $output);
        $this->assertStringContainsString('[ERROR] Task "MockTask: task2" (* * * * *) is duplicated 2 times. Make their descriptions unique to fix.', $output);
        $this->assertStringContainsString('[ERROR] Task "MockTask: task3" (* * * * *) is duplicated 3 times. Make their descriptions unique to fix.', $output);
    }

    private function normalizeOutput(CommandTester $tester): string
    {
        return \preg_replace('/\s+/', ' ', \str_replace("\n", '', $tester->getDisplay(true)));
    }
}
