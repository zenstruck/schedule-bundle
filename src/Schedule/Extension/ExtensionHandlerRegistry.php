<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\BetweenTimeHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\CallbackHandler;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExtensionHandlerRegistry
{
    private $handlers;
    private $handlerCache;

    /**
     * @param ExtensionHandler[] $handlers
     */
    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
        $this->handlerCache = [
            CallbackExtension::class => new CallbackHandler(),
            BetweenTimeExtension::class => new BetweenTimeHandler(),
        ];
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

    public function beforeSchedule(ScheduleRunContext $context): void
    {
        foreach ($context->getSchedule()->getExtensions() as $extension) {
            $this->handlerFor($extension)->filterSchedule($context, $extension);
        }

        foreach ($context->getSchedule()->getExtensions() as $extension) {
            $this->handlerFor($extension)->beforeSchedule($context, $extension);
        }
    }

    public function afterSchedule(ScheduleRunContext $context): void
    {
        foreach ($context->getSchedule()->getExtensions() as $extension) {
            $this->handlerFor($extension)->afterSchedule($context, $extension);
        }

        if ($context->isSuccessful()) {
            foreach ($context->getSchedule()->getExtensions() as $extension) {
                $this->handlerFor($extension)->onScheduleSuccess($context, $extension);
            }
        }

        if ($context->isFailure()) {
            foreach ($context->getSchedule()->getExtensions() as $extension) {
                $this->handlerFor($extension)->onScheduleFailure($context, $extension);
            }
        }
    }

    public function beforeTask(TaskRunContext $context): void
    {
        foreach ($context->getTask()->getExtensions() as $extension) {
            $this->handlerFor($extension)->filterTask($context, $extension);
        }

        foreach ($context->getTask()->getExtensions() as $extension) {
            $this->handlerFor($extension)->beforeTask($context, $extension);
        }
    }

    public function afterTask(TaskRunContext $context): void
    {
        if (!$context->hasRun()) {
            return;
        }

        foreach ($context->getTask()->getExtensions() as $extension) {
            $this->handlerFor($extension)->afterTask($context, $extension);
        }

        if ($context->isSuccessful()) {
            foreach ($context->getTask()->getExtensions() as $extension) {
                $this->handlerFor($extension)->onTaskSuccess($context, $extension);
            }
        }

        if ($context->isFailure()) {
            foreach ($context->getTask()->getExtensions() as $extension) {
                $this->handlerFor($extension)->onTaskFailure($context, $extension);
            }
        }
    }
}
