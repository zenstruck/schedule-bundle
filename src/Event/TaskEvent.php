<?php

namespace Zenstruck\ScheduleBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
abstract class TaskEvent extends Event
{
    /** @var TaskRunContext */
    private $runContext;

    final public function __construct(TaskRunContext $runContext)
    {
        $this->runContext = $runContext;
    }

    final public function runContext(): TaskRunContext
    {
        return $this->runContext;
    }
}
