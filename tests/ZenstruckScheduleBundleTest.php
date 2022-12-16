<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Zenstruck\ScheduleBundle\DependencyInjection\Compiler\ScheduleBuilderKernelPass;
use Zenstruck\ScheduleBundle\ZenstruckScheduleBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckScheduleBundleTest extends TestCase
{
    /**
     * @test
     */
    public function compiler_pass_is_added()
    {
        $container = new ContainerBuilder();
        $bundle = new ZenstruckScheduleBundle();

        $bundle->build($container);

        $foundPass = false;

        foreach ($container->getCompilerPassConfig()->getBeforeOptimizationPasses() as $pass) {
            if ($pass instanceof ScheduleBuilderKernelPass) {
                $foundPass = true;
            }
        }

        $this->assertTrue($foundPass, 'Compiler pass was not found');
    }
}
