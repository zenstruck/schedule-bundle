<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PingExtension implements HasMissingHandlerMessage
{
    private $hook;
    private $url;
    private $method;
    private $options;

    /**
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    private function __construct(string $hook, string $url, string $method = 'GET', array $options = [])
    {
        $this->hook = $hook;
        $this->url = $url;
        $this->method = $method;
        $this->options = $options;
    }

    public function __toString(): string
    {
        return "{$this->hook}, ping \"{$this->url}\"";
    }

    public function getHook(): string
    {
        return $this->hook;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    public function getMissingHandlerMessage(): string
    {
        return 'Symfony HttpClient is required to use the ping extension. Install with "composer require symfony/http-client".';
    }

    public static function taskBefore(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Task::BEFORE, $url, $method, $options);
    }

    public static function taskAfter(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Task::AFTER, $url, $method, $options);
    }

    public static function taskSuccess(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Task::SUCCESS, $url, $method, $options);
    }

    public static function taskFailure(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Task::FAILURE, $url, $method, $options);
    }

    public static function scheduleBefore(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Schedule::BEFORE, $url, $method, $options);
    }

    public static function scheduleAfter(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Schedule::AFTER, $url, $method, $options);
    }

    public static function scheduleSuccess(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Schedule::SUCCESS, $url, $method, $options);
    }

    public static function scheduleFailure(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Schedule::FAILURE, $url, $method, $options);
    }
}
