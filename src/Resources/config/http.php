<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set(\Zenstruck\ScheduleBundle\Schedule\Extension\Handler\PingHandler::class)
        ->tag('schedule.extension_handler');

    $services->set(\Zenstruck\ScheduleBundle\Schedule\Task\Runner\PingTaskRunner::class)
        ->tag('schedule.task_runner');
};
