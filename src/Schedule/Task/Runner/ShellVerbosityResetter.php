<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task\Runner;

/**
 * @internal
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ShellVerbosityResetter
{
    private $var;
    private $env;
    private $server;

    public function __construct()
    {
        $this->var = \getenv('SHELL_VERBOSITY');
        $this->env = $_ENV['SHELL_VERBOSITY'] ?? false;
        $this->server = $_SERVER['SHELL_VERBOSITY'] ?? false;
    }

    public function reset(): void
    {
        $this->resetVar();
        $this->resetEnv();
        $this->resetServer();
    }

    private function resetVar(): void
    {
        if (!\function_exists('putenv')) {
            return;
        }

        if (false === $this->var) {
            // unset as it wasn't set to begin with
            @\putenv('SHELL_VERBOSITY');

            return;
        }

        @\putenv("SHELL_VERBOSITY={$this->var}");
    }

    private function resetEnv(): void
    {
        if (false === $this->env) {
            // unset as it wasn't set to begin with
            unset($_ENV['SHELL_VERBOSITY']);

            return;
        }

        $_ENV['SHELL_VERBOSITY'] = $this->env;
    }

    private function resetServer(): void
    {
        if (false === $this->server) {
            // unset as it wasn't set to begin with
            unset($_SERVER['SHELL_VERBOSITY']);

            return;
        }

        $_SERVER['SHELL_VERBOSITY'] = $this->server;
    }
}
