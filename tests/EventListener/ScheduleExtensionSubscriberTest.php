<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\EventListener\ScheduleExtensionSubscriber;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleExtensionSubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function can_configure_schedule_with_subscriber()
    {
        $extension = new class() {
        };

        $schedule = (new MockScheduleBuilder())
            ->addSubscriber(new ScheduleExtensionSubscriber([$extension]))
            ->getRunner()
            ->buildSchedule()
        ;

        $this->assertSame($extension, $schedule->getExtensions()[0]);
    }
}
