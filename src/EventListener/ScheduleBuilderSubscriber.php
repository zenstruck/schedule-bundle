<?php

namespace Zenstruck\ScheduleBundle\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Zenstruck\ScheduleBundle\Event\ScheduleBuildEvent;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleBuilderSubscriber implements EventSubscriberInterface
{
    private $builders;

    /**
     * @param ScheduleBuilder[] $builders
     */
    public function __construct(iterable $builders)
    {
        $this->builders = $builders;
    }

    public static function getSubscribedEvents(): array
    {
        return [ScheduleBuildEvent::class => ['build', ScheduleBuildEvent::REGISTER]];
    }

    public function build(ScheduleBuildEvent $event): void
    {
        foreach ($this->builders as $builder) {
            $builder->buildSchedule($event->getSchedule());
        }
    }
}
