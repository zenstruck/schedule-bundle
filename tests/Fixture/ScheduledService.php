<?php

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
