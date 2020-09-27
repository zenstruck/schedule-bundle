<?php

namespace Zenstruck\ScheduleBundle\Tests\DependencyInjection\Compiler;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractCompilerPassTestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Zenstruck\ScheduleBundle\DependencyInjection\Compiler\ScheduleBuilderKernelPass;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleBuilderKernelPassTest extends AbstractCompilerPassTestCase
{
    /**
     * @test
     */
    public function adds_tag_if_kernel_implement_interface()
    {
        $class = new class() implements ScheduleBuilder {
            public function buildSchedule(Schedule $schedule): void
            {
            }
        };

        $this->setDefinition('kernel', new Definition(\get_class($class)));

        $this->compile();

        $this->assertContainerBuilderHasServiceDefinitionWithTag('kernel', 'schedule.builder');
    }

    /**
     * @test
     */
    public function does_not_add_tag_if_kernel_does_not_implement_interface()
    {
        $class = new class() {
        };

        $this->setDefinition('kernel', new Definition(\get_class($class)));

        $this->compile();

        $this->assertSame([], $this->container->getDefinition('kernel')->getTags());
    }

    /**
     * @test
     */
    public function does_not_add_tag_if_kernel_class_is_null()
    {
        $this->setDefinition('kernel', new Definition());

        $this->compile();

        $this->assertSame([], $this->container->getDefinition('kernel')->getTags());
    }

    protected function registerCompilerPass(ContainerBuilder $container): void
    {
        $container->addCompilerPass(new ScheduleBuilderKernelPass());
    }
}
