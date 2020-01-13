<?php

namespace Zenstruck\ScheduleBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\ScheduleBuildEvent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TimezoneSubscriber implements EventSubscriberInterface
{
    private $timezone;

    public function __construct(string $timezone)
    {
        $this->timezone = $timezone;
    }

    public static function getSubscribedEvents(): array
    {
        return [ScheduleBuildEvent::class => 'setTimezone'];
    }

    public function setTimezone(ScheduleBuildEvent $event): void
    {
        $event->getSchedule()->timezone($this->timezone);
    }
}
