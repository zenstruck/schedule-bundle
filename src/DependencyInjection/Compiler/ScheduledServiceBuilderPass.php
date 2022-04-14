<?php

namespace Zenstruck\ScheduleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Zenstruck\ScheduleBundle\Schedule\Builder\ScheduledServiceBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 *
 * @internal
 */
final class ScheduledServiceBuilderPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $builder = $container->getDefinition('zenstruck_schedule.service_builder');

        foreach ($container->findTaggedServiceIds('schedule.service') as $id => $tags) {
            foreach ($tags as $attributes) {
                ScheduledServiceBuilder::validate($container->getDefinition($id)->getClass(), $attributes);

                $builder->addMethodCall('add', [new Reference($id), $attributes]);
            }
        }
    }
}
