<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zenstruck\ScheduleBundle\Event\AfterScheduleEvent;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\PingExtension;

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
    public function beforeSchedule(BeforeScheduleEvent $event, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->beforeSchedule($event);
    }

    /**
     * @param PingExtension $extension
     */
    public function afterSchedule(AfterScheduleEvent $event, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->afterSchedule($event);
    }

    /**
     * @param PingExtension $extension
     */
    public function onScheduleSuccess(AfterScheduleEvent $event, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->onScheduleSuccess($event);
    }

    /**
     * @param PingExtension $extension
     */
    public function onScheduleFailure(AfterScheduleEvent $event, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->onScheduleFailure($event);
    }

    /**
     * @param PingExtension $extension
     */
    public function beforeTask(BeforeTaskEvent $event, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->beforeTask($event);
    }

    /**
     * @param PingExtension $extension
     */
    public function afterTask(AfterTaskEvent $event, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->afterTask($event);
    }

    /**
     * @param PingExtension $extension
     */
    public function onTaskSuccess(AfterTaskEvent $event, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->onTaskSuccess($event);
    }

    /**
     * @param PingExtension $extension
     */
    public function onTaskFailure(AfterTaskEvent $event, Extension $extension): void
    {
        $extension->setHttpClient($this->httpClient)->onTaskFailure($event);
    }

    public function supports(Extension $extension): bool
    {
        return $extension instanceof PingExtension;
    }
}
