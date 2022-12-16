<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
