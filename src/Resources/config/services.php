<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set(\Zenstruck\ScheduleBundle\Command\ScheduleListCommand::class)
        ->args([
            service(\Zenstruck\ScheduleBundle\Schedule\ScheduleRunner::class),
            service(\Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandlerRegistry::class),
        ])
        ->tag('console.command');

    $services->set(\Zenstruck\ScheduleBundle\Command\ScheduleRunCommand::class)
        ->args([
            service(\Zenstruck\ScheduleBundle\Schedule\ScheduleRunner::class),
            service('event_dispatcher'),
        ])
        ->tag('console.command');

    $services->set(\Zenstruck\ScheduleBundle\Schedule\ScheduleRunner::class)
        ->args([
            tagged_iterator('schedule.task_runner'),
            service(\Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandlerRegistry::class),
            service('event_dispatcher'),
        ]);

    $services->set(\Zenstruck\ScheduleBundle\EventListener\ScheduleBuilderSubscriber::class)
        ->args([tagged_iterator('schedule.builder')])
        ->tag('kernel.event_subscriber');

    $services->set(\Zenstruck\ScheduleBundle\EventListener\TaskConfigurationSubscriber::class)
        ->args([''])
        ->tag('kernel.event_subscriber');

    $services->set(\Zenstruck\ScheduleBundle\EventListener\ScheduleExtensionSubscriber::class)
        ->args([tagged_iterator('schedule.extension')])
        ->tag('kernel.event_subscriber');

    $services->set(\Zenstruck\ScheduleBundle\EventListener\SelfSchedulingCommandSubscriber::class)
        ->args([tagged_iterator('schedule.self_scheduling_command')])
        ->tag('kernel.event_subscriber');

    $services->set('zenstruck_schedule.console_application', \Symfony\Bundle\FrameworkBundle\Console\Application::class)
        ->args([service('kernel')]);

    $services->set(\Zenstruck\ScheduleBundle\Schedule\Task\Runner\CommandTaskRunner::class)
        ->args([service('zenstruck_schedule.console_application')])
        ->tag('schedule.task_runner');

    $services->set(\Zenstruck\ScheduleBundle\Schedule\Task\Runner\CallbackTaskRunner::class)
        ->tag('schedule.task_runner');

    $services->set(\Zenstruck\ScheduleBundle\EventListener\ScheduleLoggerSubscriber::class)
        ->args([service('logger')])
        ->tag('monolog.logger', ['channel' => 'schedule'])
        ->tag('kernel.event_subscriber');

    $services->set(\Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandlerRegistry::class)
        ->args([tagged_iterator('schedule.extension_handler')]);

    $services->set(\Zenstruck\ScheduleBundle\Schedule\Extension\Handler\EnvironmentHandler::class)
        ->args(['%kernel.environment%'])
        ->tag('schedule.extension_handler');

    $services->set('zenstruck_schedule.service_builder', \Zenstruck\ScheduleBundle\Schedule\Builder\ScheduledServiceBuilder::class)
        ->tag('schedule.builder');
};
