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
