<?php

namespace Zenstruck\ScheduleBundle\Tests\Fixture;

use Symfony\Component\Console\Command\Command;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsScheduledTask('@daily')]
#[AsScheduledTask('@weekly', description: 'run my command')]
#[AsScheduledTask('@monthly', arguments: '-vv --no-interaction')]
final class ScheduledCommand extends Command
{
    protected static $defaultName = 'my:command';
}
