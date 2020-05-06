<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\BetweenTimeExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BetweenTimeHandler extends ExtensionHandler
{
    /**
     * @param BetweenTimeExtension|Extension $extension
     */
    public function filterTask(TaskRunContext $context, Extension $extension): void
    {
        $extension->filter($context->getTask()->getTimezone());
    }

    public function supports(Extension $extension): bool
    {
        return $extension instanceof BetweenTimeExtension;
    }
}
