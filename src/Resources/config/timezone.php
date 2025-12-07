<?php

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container) {
    $services = $container->services();
    $parameters = $container->parameters();

    $services->set(\Zenstruck\ScheduleBundle\EventListener\ScheduleTimezoneSubscriber::class)
        ->args([''])
        ->tag('kernel.event_subscriber');
};
