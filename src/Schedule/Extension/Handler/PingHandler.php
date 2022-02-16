<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\MissingDependency;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\PingExtension;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PingHandler extends ExtensionHandler
{
    /** @var HttpClientInterface */
    private $httpClient;

    public function __construct(?HttpClientInterface $httpClient = null)
    {
        if (null === $httpClient && !\class_exists(HttpClient::class)) {
            throw new MissingDependency(PingExtension::getMissingDependencyMessage());
        }

        $this->httpClient = $httpClient ?: HttpClient::create();
    }

    /**
     * @param PingExtension $extension
     */
    public function beforeSchedule(ScheduleRunContext $context, object $extension): void
    {
        $this->pingIf($extension, Schedule::BEFORE);
    }

    /**
     * @param PingExtension $extension
     */
    public function afterSchedule(ScheduleRunContext $context, object $extension): void
    {
        $this->pingIf($extension, Schedule::AFTER);
    }

    /**
     * @param PingExtension $extension
     */
    public function onScheduleSuccess(ScheduleRunContext $context, object $extension): void
    {
        $this->pingIf($extension, Schedule::SUCCESS);
    }

    /**
     * @param PingExtension $extension
     */
    public function onScheduleFailure(ScheduleRunContext $context, object $extension): void
    {
        $this->pingIf($extension, Schedule::FAILURE);
    }

    /**
     * @param PingExtension $extension
     */
    public function beforeTask(TaskRunContext $context, object $extension): void
    {
        $this->pingIf($extension, Task::BEFORE);
    }

    /**
     * @param PingExtension $extension
     */
    public function afterTask(TaskRunContext $context, object $extension): void
    {
        $this->pingIf($extension, Task::AFTER);
    }

    /**
     * @param PingExtension $extension
     */
    public function onTaskSuccess(TaskRunContext $context, object $extension): void
    {
        $this->pingIf($extension, Task::SUCCESS);
    }

    /**
     * @param PingExtension $extension
     */
    public function onTaskFailure(TaskRunContext $context, object $extension): void
    {
        $this->pingIf($extension, Task::FAILURE);
    }

    public function supports(object $extension): bool
    {
        return $extension instanceof PingExtension;
    }

    private function pingIf(PingExtension $extension, string $expectedHook): void
    {
        if ($expectedHook === $extension->getHook()) {
            $this->httpClient->request($extension->getMethod(), $extension->getUrl(), $extension->getOptions())->getStatusCode();
        }
    }
}
