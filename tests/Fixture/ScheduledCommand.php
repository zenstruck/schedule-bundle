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

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Zenstruck\ScheduleBundle\Attribute\AsScheduledTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[AsCommand('my:command')]
#[AsScheduledTask('@daily')]
#[AsScheduledTask('@weekly', description: 'run my command')]
#[AsScheduledTask('@monthly', arguments: '-vv --no-interaction')]
final class ScheduledCommand extends Command
{
}
