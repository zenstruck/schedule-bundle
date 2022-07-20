<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LazyCommand;
use Symfony\Component\Console\Exception\CommandNotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CommandTask extends Task
{
    /** @var string */
    private $name;

    /** @var string */
    private $arguments = '';

    /**
     * @param string $name Command class or name (my:command)
     */
    public function __construct(string $name, string ...$arguments)
    {
        $parts = \explode(' ', $name, 2);
        $name = $parts[0];

        if (2 === \count($parts)) {
            $arguments = \array_merge([$parts[1]], $arguments);
        }

        $this->name = $name;

        if (!empty($arguments)) {
            $this->arguments(...$arguments);
        }

        parent::__construct($this->name);
    }

    public function arguments(string $argument, string ...$arguments): self
    {
        $this->arguments = \implode(' ', \array_merge([$argument], $arguments));

        return $this;
    }

    public function getArguments(): string
    {
        return (string) $this->arguments;
    }

    public function getContext(): array
    {
        return [
            'Command Name' => $this->name,
            'Command Arguments' => $this->getArguments() ?: '(none)',
        ];
    }

    public function createCommandInput(Application $application): InputInterface
    {
        return new StringInput(\implode(' ', \array_filter([
            $this->createCommand($application)->getName(),
            $this->getArguments(),
        ])));
    }

    public function createCommand(Application $application): Command
    {
        $registeredCommands = $application->all();

        if (isset($registeredCommands[$this->name])) {
            return $registeredCommands[$this->name];
        }

        foreach ($registeredCommands as $command) {
            $className = \get_class($command);
            if ($command instanceof LazyCommand) {
                $className = \get_class($command->getCommand());
            }
            if ($this->name === $className) {
                return $command;
            }
        }

        throw new CommandNotFoundException("Command \"{$this->name}\" not registered.");
    }
}
