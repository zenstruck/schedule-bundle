<?php

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
