<?php

namespace Zenstruck\ScheduleBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zenstruck\ScheduleBundle\DependencyInjection\Compiler\ScheduleBuilderKernelPass;
use Zenstruck\ScheduleBundle\DependencyInjection\Compiler\ScheduledServiceBuilderPass;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckScheduleBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ScheduleBuilderKernelPass());
        $container->addCompilerPass(new ScheduledServiceBuilderPass());
    }
}
