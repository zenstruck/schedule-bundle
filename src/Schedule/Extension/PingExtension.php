<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PingExtension extends SelfHandlingExtension
{
    private $hook;
    private $url;
    private $method;
    private $options;
    private $httpClient;

    /**
     * @param array $options See HttpClientInterface::OPTIONS_DEFAULTS
     */
    private function __construct(string $hook, string $url, string $method = 'GET', array $options = [])
    {
        if (!\interface_exists(HttpClientInterface::class)) {
            throw new \LogicException(\sprintf('Symfony HttpClient is required to use the "%s" extension. Install with "composer require symfony/http-client".', static::class));
        }

        $this->hook = $hook;
        $this->url = $url;
        $this->method = $method;
        $this->options = $options;
    }

    public function __toString(): string
    {
        return "{$this->hook}, ping \"{$this->url}\"";
    }

    public function beforeSchedule(ScheduleRunContext $context): void
    {
        $this->pingIf(self::SCHEDULE_BEFORE);
    }

    public function afterSchedule(ScheduleRunContext $context): void
    {
        $this->pingIf(self::SCHEDULE_AFTER);
    }

    public function onScheduleSuccess(ScheduleRunContext $context): void
    {
        $this->pingIf(self::SCHEDULE_SUCCESS);
    }

    public function onScheduleFailure(ScheduleRunContext $context): void
    {
        $this->pingIf(self::SCHEDULE_FAILURE);
    }

    public function beforeTask(TaskRunContext $context): void
    {
        $this->pingIf(self::TASK_BEFORE);
    }

    public function afterTask(TaskRunContext $context): void
    {
        $this->pingIf(self::TASK_AFTER);
    }

    public function onTaskSuccess(TaskRunContext $context): void
    {
        $this->pingIf(self::TASK_SUCCESS);
    }

    public function onTaskFailure(TaskRunContext $context): void
    {
        $this->pingIf(self::TASK_FAILURE);
    }

    public static function taskBefore(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Extension::TASK_BEFORE, $url, $method, $options);
    }

    public static function taskAfter(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Extension::TASK_AFTER, $url, $method, $options);
    }

    public static function taskSuccess(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Extension::TASK_SUCCESS, $url, $method, $options);
    }

    public static function taskFailure(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Extension::TASK_FAILURE, $url, $method, $options);
    }

    public static function scheduleBefore(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Extension::SCHEDULE_BEFORE, $url, $method, $options);
    }

    public static function scheduleAfter(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Extension::SCHEDULE_AFTER, $url, $method, $options);
    }

    public static function scheduleSuccess(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Extension::SCHEDULE_SUCCESS, $url, $method, $options);
    }

    public static function scheduleFailure(string $url, string $method = 'GET', array $options = []): self
    {
        return new self(Extension::SCHEDULE_FAILURE, $url, $method, $options);
    }

    public function setHttpClient(HttpClientInterface $httpClient): self
    {
        $this->httpClient = $httpClient;

        return $this;
    }

    private function pingIf(string $expectedHook): void
    {
        if ($expectedHook === $this->hook) {
            $this->getHttpClient()->request($this->method, $this->url, $this->options)->getStatusCode();
        }
    }

    private function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient ?: $this->httpClient = HttpClient::create();
    }
}
