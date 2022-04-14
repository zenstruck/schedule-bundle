<?php

namespace Zenstruck\ScheduleBundle\Tests\Fixture;

use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Zenstruck\ScheduleBundle\ZenstruckScheduleBundle;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        yield new FrameworkBundle();
        yield new ZenstruckScheduleBundle();
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'secret' => 'S3CRET',
            'router' => ['utf8' => true],
            'test' => true,
        ]);
        $c->register('logger', NullLogger::class); // disable logging
        $c->register(ScheduledService::class)->setAutowired(true)->setAutoconfigured(true);
        $c->register(ScheduledCommand::class)->setAutowired(true)->setAutoconfigured(true);
    }
}
