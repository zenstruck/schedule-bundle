<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Zenstruck\ScheduleBundle\Schedule\Extension\BetweenTimeExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BetweenTimeHandler extends ExtensionHandler
{
    /**
     * @param BetweenTimeExtension $extension
     */
    public function filterTask(TaskRunContext $context, object $extension): void
    {
        $extension->filter($context->getTask()->getTimezone());
    }

    public function supports(object $extension): bool
    {
        return $extension instanceof BetweenTimeExtension;
    }
}
