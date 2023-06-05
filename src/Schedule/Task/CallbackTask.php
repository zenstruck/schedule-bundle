<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Zenstruck\ScheduleBundle\Schedule\Extension\CallbackExtension;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CallbackTask extends Task
{
    /** @var callable */
    private $callback;

    /**
     * @param callable $callback Return value is considered "output"
     */
    public function __construct(callable $callback, ?string $description = null)
    {
        parent::__construct($description ?? '(callable) '.CallbackExtension::createDescriptionFromCallback($callback));

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
