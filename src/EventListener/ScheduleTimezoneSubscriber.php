<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\BuildScheduleEvent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleTimezoneSubscriber implements EventSubscriberInterface
{
    /** @var string */
    private $timezone;

    public function __construct(string $timezone)
    {
        $this->timezone = $timezone;
    }

    public static function getSubscribedEvents(): array
    {
        return [BuildScheduleEvent::class => 'setTimezone'];
    }

    public function setTimezone(BuildScheduleEvent $event): void
    {
        $event->getSchedule()->timezone($this->timezone);
    }
}
