<?php

namespace Zenstruck\ScheduleBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Zenstruck\ScheduleBundle\DependencyInjection\Compiler\ScheduleBuilderKernelPass;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckScheduleBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ScheduleBuilderKernelPass());
    }
}
