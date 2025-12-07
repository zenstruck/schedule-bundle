<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set(\Zenstruck\ScheduleBundle\Schedule\Extension\Handler\SingleServerHandler::class)
        ->args([''])
        ->tag('schedule.extension_handler');
};
