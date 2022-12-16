<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Schedule\Exception\MissingDependency;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\BetweenTimeHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\CallbackHandler;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ExtensionHandlerRegistry
{
    /** @var iterable<ExtensionHandler> */
    private $handlers;

    /** @var array<string,ExtensionHandler> */
    private $handlerCache;

    /**
     * @param iterable<ExtensionHandler> $handlers
     */
    public function __construct(iterable $handlers)
    {
        $this->handlers = $handlers;
        $this->handlerCache = [
            CallbackExtension::class => new CallbackHandler(),
            BetweenTimeExtension::class => new BetweenTimeHandler(),
        ];
    }

    public function handlerFor(object $extension): ExtensionHandler
    {
        $class = $extension::class;

        if (isset($this->handlerCache[$class])) {
            return $this->handlerCache[$class];
        }

        foreach ($this->handlers as $handler) {
            if ($handler->supports($extension)) {
                return $this->handlerCache[$class] = $handler;
            }
        }

        throw MissingDependency::noExtensionHandler($extension);
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
