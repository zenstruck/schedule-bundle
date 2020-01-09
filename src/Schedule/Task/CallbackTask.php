<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Zenstruck\ScheduleBundle\Schedule\Extension\CallbackExtension;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CallbackTask extends Task implements SelfRunningTask
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

    public function __invoke(): Result
    {
        $output = ($this->callback)();

        return Result::successful($this, self::stringify($output));
    }

    /**
     * @param mixed $value
     */
    private static function stringify($value): ?string
    {
        if (null === $value) {
            return null;
        }

        if (\is_scalar($value)) {
            return $value;
        }

        if (\is_object($value) && \method_exists($value, '__toString')) {
            return $value;
        }

        if (\is_object($value)) {
            return '[object] '.\get_class($value);
        }

        return '('.\gettype($value).')';
    }
}
