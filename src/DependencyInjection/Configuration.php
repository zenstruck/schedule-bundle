<?php

namespace Zenstruck\ScheduleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('zenstruck_schedule');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('without_overlapping_handler')
                    ->info('The LockFactory service to use')
                    ->example('lock.default.factory')
                    ->defaultNull()
                ->end()
                ->scalarNode('single_server_handler')
                    ->info('The LockFactory service to use')
                    ->example('lock.default.factory')
                    ->defaultNull()
                ->end()
                ->scalarNode('ping_handler')
                    ->info('The HttpClient service to use')
                    ->example('http_client')
                    ->defaultNull()
                ->end()
                ->scalarNode('timezone')
                    ->info('The timezone for tasks (override at task level), null for system default')
                    ->example('America/New_York')
                    ->defaultNull()
                    ->validate()
                        ->ifNotInArray(\timezone_identifiers_list())
                        ->thenInvalid('Timezone %s is not available')
                    ->end()
                ->end()
                ->arrayNode('email_handler')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('service')
                            ->defaultValue('mailer')
                            ->info('The mailer service to use')
                        ->end()
                        ->scalarNode('default_from')
                            ->info('The default "from" email address (use if no mailer default from is configured)')
                            ->defaultNull()
                        ->end()
                        ->scalarNode('default_to')
                            ->info('The default "to" email address (can be overridden by extension)')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('schedule_extensions')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->arrayNode('environments')
                            ->beforeNormalization()->castToArray()->end()
                            ->scalarPrototype()->end()
                            ->info('Set the environment(s) you only want the schedule to run in.')
                            ->example('[prod, staging]')
                        ->end()
                        ->arrayNode('on_single_server')
                            ->info('Run schedule on only one server')
                            ->canBeEnabled()
                            ->children()
                                ->integerNode('ttl')
                                    ->info('Maximum expected lock duration in seconds')
                                    ->defaultValue(SingleServerExtension::DEFAULT_TTL)
                                ->end()
                            ->end()
                        ->end()
                        ->arrayNode('email_on_failure')
                            ->info('Send email if schedule fails')
                            ->canBeEnabled()
                            ->children()
                                ->scalarNode('to')
                                    ->info('Email address to send email to (leave blank to use "zenstruck_schedule.email_handler.default_to")')
                                    ->defaultNull()
                                ->end()
                                ->scalarNode('subject')
                                    ->info('Email subject (leave blank to use extension default)')
                                    ->defaultNull()
                                ->end()
                            ->end()
                        ->end()
                        ->append($this->createPingExtension('ping_before', 'Ping a url before schedule runs'))
                        ->append($this->createPingExtension('ping_after', 'Ping a url after schedule runs'))
                        ->append($this->createPingExtension('ping_on_success', 'Ping a url if the schedule successfully ran'))
                        ->append($this->createPingExtension('ping_on_failure', 'Ping a url if the schedule failed'))
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function createPingExtension(string $name, string $description): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder($name);
        $node = $treeBuilder->getRootNode();

        $node
            ->info($description)
            ->canBeEnabled()
            ->children()
                ->scalarNode('url')
                    ->info('The url to ping')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('method')
                    ->info('The HTTP method to use')
                    ->defaultValue('GET')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('options')
                    ->info('See HttpClientInterface::OPTIONS_DEFAULTS')
                    ->scalarPrototype()->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
