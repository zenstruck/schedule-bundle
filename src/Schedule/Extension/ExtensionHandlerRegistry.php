<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Event\AfterScheduleEvent;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Schedule\Extension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExtensionHandlerRegistry
{
    private $handlers;
    private $handlerCache = [];

    /**
     * @param ExtensionHandler[] $handlers
     */
    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
    }

    public function handlerFor(Extension $extension): ExtensionHandler
    {
        $class = \get_class($extension);

        if (isset($this->handlerCache[$class])) {
            return $this->handlerCache[$class];
        }

        foreach ($this->handlers as $handler) {
            if ($handler->supports($extension)) {
                return $this->handlerCache[$class] = $handler;
            }
        }

        $message = \sprintf('No extension handler registered for "%s: %s".', \get_class($extension), $extension);

        if ($extension instanceof HasMissingHandlerMessage) {
            $message = $extension->getMissingHandlerMessage();
        }

        throw new \LogicException($message);
    }

    public function beforeSchedule(BeforeScheduleEvent $event): void
    {
        foreach ($event->getScheduleRunContext()->scheduleExtensions() as $extension) {
            $this->handlerFor($extension)->filterSchedule($event, $extension);
        }

        foreach ($event->getScheduleRunContext()->scheduleExtensions() as $extension) {
            $this->handlerFor($extension)->beforeSchedule($event, $extension);
        }
    }

    public function afterSchedule(AfterScheduleEvent $event): void
    {
        foreach ($event->getScheduleRunContext()->scheduleExtensions() as $extension) {
            $this->handlerFor($extension)->afterSchedule($event, $extension);
        }

        if ($event->isSuccessful()) {
            foreach ($event->getScheduleRunContext()->scheduleExtensions() as $extension) {
                $this->handlerFor($extension)->onScheduleSuccess($event, $extension);
            }
        }

        if ($event->isFailure()) {
            foreach ($event->getScheduleRunContext()->scheduleExtensions() as $extension) {
                $this->handlerFor($extension)->onScheduleFailure($event, $extension);
            }
        }
    }

    public function beforeTask(BeforeTaskEvent $event): void
    {
        foreach ($event->getTask()->getExtensions() as $extension) {
            $this->handlerFor($extension)->filterTask($event, $extension);
        }

        foreach ($event->getTask()->getExtensions() as $extension) {
            $this->handlerFor($extension)->beforeTask($event, $extension);
        }
    }

    public function afterTask(AfterTaskEvent $event): void
    {
        if (!$event->getResult()->hasRun()) {
            return;
        }

        foreach ($event->getTask()->getExtensions() as $extension) {
            $this->handlerFor($extension)->afterTask($event, $extension);
        }

        if ($event->isSuccessful()) {
            foreach ($event->getTask()->getExtensions() as $extension) {
                $this->handlerFor($extension)->onTaskSuccess($event, $extension);
            }
        }

        if ($event->isFailure()) {
            foreach ($event->getTask()->getExtensions() as $extension) {
                $this->handlerFor($extension)->onTaskFailure($event, $extension);
            }
        }
    }
}
