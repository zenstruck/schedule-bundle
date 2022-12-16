<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests\Fixture;

use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsScheduledTask('@daily')]
#[AsScheduledTask('@weekly', description: 'custom description')]
#[AsScheduledTask('@monthly', method: 'someMethod')]
final class ScheduledService
{
    public function __invoke(): void
    {
    }

    public function someMethod(): void
    {
    }
}
