<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Exception;

use Zenstruck\ScheduleBundle\Schedule\HasMissingDependencyMessage;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MissingDependency extends \LogicException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function noTaskRunner(Task $task): self
    {
        if ($task instanceof HasMissingDependencyMessage) {
            return new self($task::getMissingDependencyMessage());
        }

        return new self(\sprintf('No task runner registered for "%s".', $task));
    }

    public static function noExtensionHandler(object $extension): self
    {
        if ($extension instanceof HasMissingDependencyMessage) {
            return new self($extension::getMissingDependencyMessage());
        }

        if (\method_exists($extension, '__toString')) {
            return new self(\sprintf('No extension handler registered for "%s: %s".', $extension::class, $extension));
        }

        return new self(\sprintf('No extension handler registered for "%s".', $extension::class));
    }
}
