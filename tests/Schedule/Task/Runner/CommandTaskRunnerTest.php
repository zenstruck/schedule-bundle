<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task\Runner;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;
use Zenstruck\ScheduleBundle\Schedule\Task\Runner\CommandTaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CommandTaskRunnerTest extends TestCase
{
    /**
     * @test
     */
    public function creates_successful_result()
    {
        $application = new Application();
        $application->add($this->createCommand());
        $runner = new CommandTaskRunner($application);

        $result = $runner(new CommandTask('my:command'));

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('some output...', $result->getOutput());
    }

    /**
     * @test
     */
    public function creates_exception_result()
    {
        $application = new Application();
        $application->add($this->createCommand());
        $runner = new CommandTaskRunner($application);

        $result = $runner(new CommandTask('my:command --exception'));

        $this->assertTrue($result->isFailure());
        $this->assertTrue($result->isException());
        $this->assertInstanceOf(\RuntimeException::class, $result->getException());
        $this->assertSame('RuntimeException: exception message', $result->getDescription());
        $this->assertSame('some output...', $result->getOutput());
    }

    /**
     * @test
     */
    public function creates_failure_result()
    {
        $application = new Application();
        $application->add($this->createCommand());
        $runner = new CommandTaskRunner($application);

        $result = $runner(new CommandTask('my:command --fail'));

        $this->assertTrue($result->isFailure());
        $this->assertSame('Exit 1: General error', $result->getDescription());
        $this->assertSame('some output...', $result->getOutput());
    }

    /**
     * @test
     */
    public function shell_verbosity_is_reset(): void
    {
        $preShellVerbosity = [
            \getenv('SHELL_VERBOSITY'),
            $_ENV['SHELL_VERBOSITY'] ?? false,
            $_SERVER['SHELL_VERBOSITY'] ?? false,
        ];

        $application = new Application();
        $application->add($this->createCommand());
        $runner = new CommandTaskRunner($application);

        $result = $runner(new CommandTask('my:command -vv'));

        $this->assertTrue($result->isSuccessful());
        $this->assertSame('some output... is very verbose', $result->getOutput());
        $this->assertSame($preShellVerbosity, [
            \getenv('SHELL_VERBOSITY'),
            $_ENV['SHELL_VERBOSITY'] ?? false,
            $_SERVER['SHELL_VERBOSITY'] ?? false,
        ]);
    }

    /**
     * @test
     */
    public function supports_command_task()
    {
        $this->assertTrue((new CommandTaskRunner(new Application()))->supports(new CommandTask('my:command')));
    }

    private function createCommand(): Command
    {
        return new class() extends Command {
            public static function getDefaultName(): string
            {
                return 'my:command';
            }

            protected function configure()
            {
                $this
                    ->addOption('fail')
                    ->addOption('exception')
                ;
            }

            protected function execute(InputInterface $input, OutputInterface $output)
            {
                $output->write('some output...');

                if ($output->isVeryVerbose()) {
                    $output->write(' is very verbose');
                }

                if ($input->getOption('exception')) {
                    throw new \RuntimeException('exception message');
                }

                return $input->getOption('fail') ? 1 : 0;
            }
        };
    }
}
