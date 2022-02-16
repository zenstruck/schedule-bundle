<?php

namespace Zenstruck\ScheduleBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleBuilderKernelPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition('kernel')) {
            return;
        }

        $kernel = $container->getDefinition('kernel');

        if (null === $class = $kernel->getClass()) {
            return;
        }

        /** @var class-string $class */
        if ((new \ReflectionClass($class))->implementsInterface(ScheduleBuilder::class)) {
            $kernel->addTag('schedule.builder');
        }
    }
}
