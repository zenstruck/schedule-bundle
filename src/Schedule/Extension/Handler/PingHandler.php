<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

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

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    /**
     * @param PingExtension $extension
     */
    public function beforeSchedule(ScheduleRunContext $context, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->beforeSchedule($context);
    }

    /**
     * @param PingExtension $extension
     */
    public function afterSchedule(ScheduleRunContext $context, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->afterSchedule($context);
    }

    /**
     * @param PingExtension $extension
     */
    public function onScheduleSuccess(ScheduleRunContext $context, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->onScheduleSuccess($context);
    }

    /**
     * @param PingExtension $extension
     */
    public function onScheduleFailure(ScheduleRunContext $context, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->onScheduleFailure($context);
    }

    /**
     * @param PingExtension $extension
     */
    public function beforeTask(TaskRunContext $context, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->beforeTask($context);
    }

    /**
     * @param PingExtension $extension
     */
    public function afterTask(TaskRunContext $context, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->afterTask($context);
    }

    /**
     * @param PingExtension $extension
     */
    public function onTaskSuccess(TaskRunContext $context, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->onTaskSuccess($context);
    }

    /**
     * @param PingExtension $extension
     */
    public function onTaskFailure(TaskRunContext $context, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->onTaskFailure($context);
    }

    public function supports(Extension $extension): bool
    {
        return $extension instanceof PingExtension;
    }
}
