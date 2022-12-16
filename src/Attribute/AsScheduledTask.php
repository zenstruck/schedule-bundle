<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Attribute;

/**
 * Schedule an invokable service or console command.
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class AsScheduledTask
{
    public function __construct(
        /**
         * Cron expression or alias.
         */
        public string $frequency,

        /**
         * Task description.
         */
        public ?string $description = null,

        /**
         * The invokable service method to be called when run (must
         * have no required parameters).
         *
         * Only applicable to "invokable services".
         */
        public string $method = '__invoke',

        /**
         * The command arguments (ie "-v --no-interaction").
         *
         * Only applicable to "console commands".
         */
        public ?string $arguments = null,
    ) {
    }
}
