<?php

declare(strict_types=1);

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule;

use Zenstruck\ScheduleBundle\Schedule\Task\Result;

trait TaskOutput
{
    private function getTaskOutput(Result $result, ScheduleRunContext $context, bool $includeException = true): string
    {
        $output = '';

        if ($context->isForceRun()) {
            $output = "!! This task was force run !!\n\n";
        }

        $output .= \sprintf("Result: \"%s\"\n\nTask ID: %s", $result, $result->getTask()->getId());

        if ($result->getOutput()) {
            $output .= "\n\n## Task Output:\n\n{$result->getOutput()}";
        }

        if ($result->isException() && $includeException) {
            $output .= "\n\n## Exception:\n\n{$result->getException()}";
        }

        return $output;
    }
}
