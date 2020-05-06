<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Zenstruck\ScheduleBundle\Schedule\Extension\CallbackExtension;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CallbackTask extends Task
{
    private $callback;

    /**
     * @param callable $callback Return value is considered "output"
     */
    public function __construct(callable $callback)
    {
        parent::__construct('(callable) '.CallbackExtension::createDescriptionFromCallback($callback));

        $this->callback = $callback;
    }

    public function getCallback(): callable
    {
        return $this->callback;
    }

    public function getContext(): array
    {
        return ['Callable' => CallbackExtension::createDescriptionFromCallback($this->callback)];
    }
}
