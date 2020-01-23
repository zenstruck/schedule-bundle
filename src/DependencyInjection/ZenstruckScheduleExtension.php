<?php

namespace Zenstruck\ScheduleBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\ConfigurableExtension;
use Zenstruck\ScheduleBundle\EventListener\ScheduleTimezoneSubscriber;
use Zenstruck\ScheduleBundle\EventListener\TaskConfigurationSubscriber;
use Zenstruck\ScheduleBundle\Schedule\Extension\EmailExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\EnvironmentExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\EmailHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\PingHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\SingleServerHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\WithoutOverlappingHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\PingExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
use Zenstruck\ScheduleBundle\Schedule\SelfSchedulingCommand;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckScheduleExtension extends ConfigurableExtension
{
    protected function loadInternal(array $mergedConfig, ContainerBuilder $container): void
    {
        $container->registerForAutoconfiguration(ScheduleBuilder::class)
            ->addTag('schedule.builder')
        ;

        $container->registerForAutoconfiguration(TaskRunner::class)
            ->addTag('schedule.task_runner')
        ;

        $container->registerForAutoconfiguration(SelfSchedulingCommand::class)
            ->addTag('schedule.self_scheduling_command')
        ;

        $container->registerForAutoconfiguration(ExtensionHandler::class)
            ->addTag('schedule.extension_handler')
        ;

        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        if ($mergedConfig['without_overlapping_handler']) {
            $loader->load('without_overlapping.xml');

            $container
                ->getDefinition(WithoutOverlappingHandler::class)
                ->setArgument(0, new Reference($mergedConfig['without_overlapping_handler']))
            ;
        }

        if ($mergedConfig['single_server_handler']) {
            $loader->load('single_server.xml');

            $container
                ->getDefinition(SingleServerHandler::class)
                ->setArgument(0, new Reference($mergedConfig['single_server_handler']))
            ;
        }

        if ($mergedConfig['ping_handler']) {
            $loader->load('ping.xml');

            $container
                ->getDefinition(PingHandler::class)
                ->setArgument(0, new Reference($mergedConfig['ping_handler']))
            ;
        }

        if ($mergedConfig['timezone']) {
            $loader->load('timezone.xml');
            $container
                ->getDefinition(ScheduleTimezoneSubscriber::class)
                ->setArgument(0, $mergedConfig['timezone'])
            ;
        }

        if ($mergedConfig['email_handler']['enabled']) {
            $loader->load('email_handler.xml');

            $container
                ->getDefinition(EmailHandler::class)
                ->setArguments([
                    new Reference($mergedConfig['email_handler']['service']),
                    $mergedConfig['email_handler']['default_from'],
                    $mergedConfig['email_handler']['default_to'],
                    $mergedConfig['email_handler']['subject_prefix'],
                ])
            ;
        }

        $this->registerTaskConfiguration($mergedConfig['tasks'], $container);
        $this->registerScheduleExtensions($mergedConfig, $container);
    }

    private function registerTaskConfiguration(array $config, ContainerBuilder $container): void
    {
        $container
            ->getDefinition(TaskConfigurationSubscriber::class)
            ->setArgument(0, $config)
        ;

        $taskServices = [];

        foreach ($config as $taskConfig) {
            foreach ($taskConfig['task'] as $value) {
                if (0 === \mb_strpos($value, '@')) {
                    $id = \mb_substr($value, 1);
                    $taskServices[$id] = new Reference($id);
                }
            }
        }

        $container
            ->getDefinition('zenstruck_schedule.task_locator')
            ->setArgument(0, $taskServices)
        ;
    }

    private function registerScheduleExtensions(array $config, ContainerBuilder $container): void
    {
        /** @var Definition[] $definitions */
        $definitions = [];
        $idPrefix = 'zenstruck_schedule.extension.';

        if (!empty($config['schedule_extensions']['environments'])) {
            $definitions[$idPrefix.'environments'] = new Definition(
                EnvironmentExtension::class,
                [$config['schedule_extensions']['environments']]
            );
        }

        if ($config['schedule_extensions']['on_single_server']['enabled']) {
            $definitions[$idPrefix.'on_single_server'] = new Definition(
                SingleServerExtension::class,
                [$config['schedule_extensions']['on_single_server']['ttl']]
            );
        }

        if ($config['schedule_extensions']['email_on_failure']['enabled']) {
            $definition = new Definition(EmailExtension::class);
            $definition->setFactory([EmailExtension::class, 'scheduleFailure']);
            $definition->setArguments([
                $config['schedule_extensions']['email_on_failure']['to'],
                $config['schedule_extensions']['email_on_failure']['subject'],
            ]);

            $definitions[$idPrefix.'email_on_failure'] = $definition;
        }

        $pingMap = [
            'ping_before' => 'scheduleBefore',
            'ping_after' => 'scheduleAfter',
            'ping_on_success' => 'scheduleSuccess',
            'ping_on_failure' => 'scheduleFailure',
        ];

        foreach ($pingMap as $key => $method) {
            if ($config['schedule_extensions'][$key]['enabled']) {
                $definition = new Definition(PingExtension::class);
                $definition->setFactory([PingExtension::class, $method]);
                $definition->setArguments([
                    $config['schedule_extensions'][$key]['url'],
                    $config['schedule_extensions'][$key]['method'],
                    $config['schedule_extensions'][$key]['options'],
                ]);

                $definitions[$idPrefix.$key] = $definition;
            }
        }

        foreach ($definitions as $definition) {
            $definition->addTag('schedule.extension');
        }

        $container->addDefinitions($definitions);
    }
}
