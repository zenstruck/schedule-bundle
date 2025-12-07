<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set(\Zenstruck\ScheduleBundle\Schedule\Task\Runner\ProcessTaskRunner::class)
        ->tag('schedule.task_runner');
};
