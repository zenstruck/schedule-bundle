<?php

declare(strict_types=1);

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
