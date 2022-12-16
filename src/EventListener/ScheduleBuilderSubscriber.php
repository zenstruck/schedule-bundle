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
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleBuilderSubscriber implements EventSubscriberInterface
{
    /** @var iterable<ScheduleBuilder> */
    private $builders;

    /**
     * @param iterable<ScheduleBuilder> $builders
     */
    public function __construct(iterable $builders)
    {
        $this->builders = $builders;
    }

    public static function getSubscribedEvents(): array
    {
        return [BuildScheduleEvent::class => 'build'];
    }

    public function build(BuildScheduleEvent $event): void
    {
        foreach ($this->builders as $builder) {
            $builder->buildSchedule($event->getSchedule());
        }
    }
}
