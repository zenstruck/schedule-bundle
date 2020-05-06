<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\PingExtension;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class PingHandler extends ExtensionHandler
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient = null)
    {
        if (null === $httpClient && !\class_exists(HttpClient::class)) {
            throw new \LogicException(\sprintf('Symfony HttpClient is required to use the "%s" extension. Install with "composer require symfony/http-client".', PingExtension::class));
        }

        $this->httpClient = $httpClient ?: HttpClient::create();
    }

    /**
     * @param PingExtension|Extension $extension
     */
    public function beforeSchedule(ScheduleRunContext $context, Extension $extension): void
    {
        $this->pingIf($extension, Extension::SCHEDULE_BEFORE);
    }

    /**
     * @param PingExtension|Extension $extension
     */
    public function afterSchedule(ScheduleRunContext $context, Extension $extension): void
    {
        $this->pingIf($extension, Extension::SCHEDULE_AFTER);
    }

    /**
     * @param PingExtension|Extension $extension
     */
    public function onScheduleSuccess(ScheduleRunContext $context, Extension $extension): void
    {
        $this->pingIf($extension, Extension::SCHEDULE_SUCCESS);
    }

    /**
     * @param PingExtension|Extension $extension
     */
    public function onScheduleFailure(ScheduleRunContext $context, Extension $extension): void
    {
        $this->pingIf($extension, Extension::SCHEDULE_FAILURE);
    }

    /**
     * @param PingExtension|Extension $extension
     */
    public function beforeTask(TaskRunContext $context, Extension $extension): void
    {
        $this->pingIf($extension, Extension::TASK_BEFORE);
    }

    /**
     * @param PingExtension|Extension $extension
     */
    public function afterTask(TaskRunContext $context, Extension $extension): void
    {
        $this->pingIf($extension, Extension::TASK_AFTER);
    }

    /**
     * @param PingExtension|Extension $extension
     */
    public function onTaskSuccess(TaskRunContext $context, Extension $extension): void
    {
        $this->pingIf($extension, Extension::TASK_SUCCESS);
    }

    /**
     * @param PingExtension|Extension $extension
     */
    public function onTaskFailure(TaskRunContext $context, Extension $extension): void
    {
        $this->pingIf($extension, Extension::TASK_FAILURE);
    }

    public function supports(Extension $extension): bool
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
