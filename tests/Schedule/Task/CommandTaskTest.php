<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\StringInput;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CommandTaskTest extends TestCase
{
    /**
     * @test
     */
    public function has_default_description()
    {
        $this->assertSame('my:command', (new CommandTask('my:command', '--option'))->getDescription());
        $this->assertSame(DummyCommand::class, (new CommandTask(DummyCommand::class, '--option'))->getDescription());
    }

    /**
     * @test
     */
    public function can_create_with_arguments()
    {
        $task1 = new CommandTask('my:command');
        $this->assertSame('CommandTask: my:command', (string) $task1);
        $this->assertSame('', $task1->getArguments());

        $task2 = new CommandTask('my:command arg');
        $this->assertSame('CommandTask: my:command', (string) $task2);
        $this->assertSame('arg', $task2->getArguments());

        $task3 = new CommandTask('my:command arg', '--option1 --option2', '--option3');
        $this->assertSame('CommandTask: my:command', (string) $task3);
        $this->assertSame('arg --option1 --option2 --option3', $task3->getArguments());

        $task4 = new CommandTask('my:command arg', '--option1 --option2', '--option3');
        $task4->arguments('--option1');
        $this->assertSame('CommandTask: my:command', (string) $task4);
        $this->assertSame('--option1', $task4->getArguments());
    }

    /**
     * @test
     */
    public function task_has_context()
    {
        $task = new CommandTask('my:command');
        $this->assertSame(['Command Name' => 'my:command', 'Command Arguments' => '(none)'], $task->getContext());

        $task = new CommandTask('my:command --foo bar');
        $this->assertSame(['Command Name' => 'my:command', 'Command Arguments' => '--foo bar'], $task->getContext());
    }

    /**
     * @test
     * @dataProvider commandNameProvider
     */
    public function can_create_input($commandName)
    {
        $application = new Application();
        $application->add(new DummyCommand());

        $task = new CommandTask($commandName, '--option');
        $input = $task->createCommandInput($application);

        $this->assertInstanceOf(StringInput::class, $input);
        $this->assertSame("'dummy:command' --option", (string) $input);
    }

    public static function commandNameProvider()
    {
        return [
            ['dummy:command'],
            [DummyCommand::class],
        ];
    }

    /**
     * @test
     */
    public function unregistered_command_throws_exception()
    {
        $task = new CommandTask('invalid:command');

        $this->expectExceptionMessage(CommandNotFoundException::class);
        $this->expectExceptionMessage('Command "invalid:command" not registered.');

        $task->createCommandInput(new Application());
    }
}

final class DummyCommand extends Command
{
    protected static $defaultName = 'dummy:command';
}
