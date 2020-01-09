<?php

namespace Zenstruck\ScheduleBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\ScheduleBuildEvent;
use Zenstruck\ScheduleBundle\Schedule\Extension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ConfigureScheduleSubscriber implements EventSubscriberInterface
{
    private $extensions;

    /**
     * @param Extension[] $extensions
     */
    public function __construct(iterable $extensions)
    {
        $this->extensions = $extensions;
    }

    public static function getSubscribedEvents(): array
    {
        return [ScheduleBuildEvent::class => ['configureSchedule', ScheduleBuildEvent::POST_REGISTER]];
    }

    public function configureSchedule(ScheduleBuildEvent $event): void
    {
        foreach ($this->extensions as $extension) {
            $event->getSchedule()->addExtension($extension);
        }
    }
}
