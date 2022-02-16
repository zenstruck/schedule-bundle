<?php

namespace Zenstruck\ScheduleBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\BuildScheduleEvent;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleExtensionSubscriber implements EventSubscriberInterface
{
    /** @var iterable<object> */
    private $extensions;

    /**
     * @param iterable<object> $extensions
     */
    public function __construct(iterable $extensions)
    {
        $this->extensions = $extensions;
    }

    public static function getSubscribedEvents(): array
    {
        return [BuildScheduleEvent::class => 'addExtensions'];
    }

    public function addExtensions(BuildScheduleEvent $event): void
    {
        foreach ($this->extensions as $extension) {
            $event->getSchedule()->addExtension($extension);
        }
    }
}
