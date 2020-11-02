<?php

namespace Zenstruck\ScheduleBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Zenstruck\ScheduleBundle\Schedule\CronExpression;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\WithoutOverlappingExtension;

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
                ->scalarNode('without_overlapping_lock_factory')
                    ->info('The LockFactory service to use for the without overlapping extension')
                    ->example('lock.default.factory')
                    ->defaultNull()
                ->end()
                ->scalarNode('single_server_lock_factory')
                    ->info('The LockFactory service to use for the single server extension - be sure to use a "remote store" (https://symfony.com/doc/current/components/lock.html#remote-stores)')
                    ->example('lock.redis.factory')
                    ->defaultNull()
                ->end()
                ->scalarNode('http_client')
                    ->info('The HttpClient service to use')
                    ->example('http_client')
                    ->defaultNull()
                ->end()
                ->scalarNode('timezone')
                    ->info('The default timezone for tasks (override at task level), null for system default')
                    ->example('America/New_York')
                    ->defaultNull()
                    ->validate()
                        ->ifNotInArray(\timezone_identifiers_list())
                        ->thenInvalid('Timezone %s is not available')
                    ->end()
                ->end()
                ->arrayNode('messenger')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('message_bus')
                            ->defaultValue('message_bus')
                            ->cannotBeEmpty()
                            ->info('The message bus to use')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('mailer')
                    ->canBeEnabled()
                    ->children()
                        ->scalarNode('service')
                            ->defaultValue('mailer')
                            ->cannotBeEmpty()
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
                        ->scalarNode('subject_prefix')
                            ->info('The prefix to use for email subjects (use to distinguish between different application schedules)')
                            ->example('"[Acme Inc Website]"')
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
                        ->append(self::createEmailExtension('email_on_failure', 'Send email if schedule fails'))
                        ->append(self::createPingExtension('ping_before', 'Ping a url before schedule runs'))
                        ->append(self::createPingExtension('ping_after', 'Ping a url after schedule runs'))
                        ->append(self::createPingExtension('ping_on_success', 'Ping a url if the schedule successfully ran'))
                        ->append(self::createPingExtension('ping_on_failure', 'Ping a url if the schedule failed'))
                    ->end()
                ->end()
                ->append(self::taskConfiguration())
            ->end()
        ;

        return $treeBuilder;
    }

    private static function taskConfiguration(): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder('tasks');
        $node = $treeBuilder->getRootNode();

        $node
            ->example([
                [
                    'task' => 'send:sales-report --detailed',
                    'frequency' => '0 * * * *',
                    'description' => 'Send sales report hourly',
                    'without_overlapping' => '~',
                    'only_between' => '9-17',
                    'ping_on_success' => 'https://example.com/hourly-report-health-check',
                    'email_on_failure' => 'sales@example.com',
                ],
            ])
            ->arrayPrototype()
                ->validate()
                    ->ifTrue(function($v) {
                        return [null] === $v['task'] && !$v['description'];
                    })
                    ->thenInvalid('"null" tasks must have a description.')
                ->end()
                ->children()
                    ->arrayNode('task')
                        ->info('Defaults to CommandTask, prefix with "bash:" to create ProcessTask, prefix url with "ping:" to create PingTask, pass array of commands to create CompoundTask (optionally keyed by description)')
                        ->example('"my:command arg1 --option1=value", "bash:/bin/my-script" or "ping:https://example.com"')
                        ->validate()
                            ->ifTrue(function($v) {
                                foreach ($v as $item) {
                                    if ('' === (string) $item) {
                                        return true;
                                    }
                                }

                                return false;
                            })
                            ->thenInvalid('Task cannot be empty value.')
                        ->end()
                        ->beforeNormalization()
                            ->castToArray()
                        ->end()
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->scalarPrototype()->end()
                    ->end()
                    ->scalarNode('frequency')
                        ->info('Cron expression')
                        ->example('0 * * * *')
                        ->isRequired()
                        ->cannotBeEmpty()
                        ->validate()
                            ->ifTrue(function($v) {
                                try {
                                    new CronExpression($v, 'context');
                                } catch (\InvalidArgumentException $e) {
                                    return true;
                                }

                                return false;
                            })
                            ->thenInvalid('%s is an invalid cron expression.')
                        ->end()
                    ->end()
                    ->scalarNode('description')
                        ->info('Task description')
                        ->defaultNull()
                    ->end()
                    ->scalarNode('timezone')
                        ->info('The timezone for this task, null for system default')
                        ->example('America/New_York')
                        ->defaultNull()
                        ->validate()
                            ->ifNotInArray(\timezone_identifiers_list())
                            ->thenInvalid('Timezone %s is not available')
                        ->end()
                    ->end()
                    ->arrayNode('without_overlapping')
                        ->info('Prevent task from running if still running from previous run')
                        ->canBeEnabled()
                        ->children()
                            ->integerNode('ttl')
                                ->info('Maximum expected lock duration in seconds')
                                ->defaultValue(WithoutOverlappingExtension::DEFAULT_TTL)
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('only_between')
                        ->info('Only run between given times (alternatively enable by passing a range, ie "9:00-17:00"')
                        ->canBeEnabled()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function($v) {
                                [$start, $end] = \explode('-', $v);

                                return [
                                    'enabled' => true,
                                    'start' => $start,
                                    'end' => $end,
                                ];
                            })
                        ->end()
                        ->children()
                            ->scalarNode('start')
                                ->example('9:00')
                                ->isRequired()
                            ->end()
                            ->scalarNode('end')
                                ->example('17:00')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                    ->arrayNode('unless_between')
                        ->info('Skip when between given times (alternatively enable by passing a range, ie "17:00-06:00"')
                        ->canBeEnabled()
                        ->beforeNormalization()
                            ->ifString()
                            ->then(function($v) {
                                [$start, $end] = \explode('-', $v);

                                return [
                                    'enabled' => true,
                                    'start' => $start,
                                    'end' => $end,
                                ];
                            })
                        ->end()
                        ->children()
                            ->scalarNode('start')
                                ->example('17:00')
                                ->isRequired()
                            ->end()
                                ->scalarNode('end')
                                ->example('06:00')
                                ->isRequired()
                            ->end()
                        ->end()
                    ->end()
                    ->append(self::createPingExtension('ping_before', 'Ping a url before task runs'))
                    ->append(self::createPingExtension('ping_after', 'Ping a url after task runs'))
                    ->append(self::createPingExtension('ping_on_success', 'Ping a url if the task successfully ran'))
                    ->append(self::createPingExtension('ping_on_failure', 'Ping a url if the task failed'))
                    ->append(self::createEmailExtension('email_after', 'Send email after task runs'))
                    ->append(self::createEmailExtension('email_on_failure', 'Send email if task fails'))
                ->end()
            ->end()
        ;

        return $node;
    }

    private static function createEmailExtension(string $name, string $description): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder($name);
        $node = $treeBuilder->getRootNode();

        $node
            ->info($description.' (alternatively enable by passing a "to" email)')
            ->canBeEnabled()
            ->beforeNormalization()
                ->ifString()
                ->then(function($v) {
                    return [
                        'enabled' => true,
                        'to' => $v,
                        'subject' => null,
                    ];
                })
            ->end()
            ->children()
                ->scalarNode('to')
                    ->info('Email address to send email to (leave blank to use "zenstruck_schedule.mailer.default_to")')
                    ->defaultNull()
                ->end()
                ->scalarNode('subject')
                    ->info('Email subject (leave blank to use extension default)')
                    ->defaultNull()
                ->end()
            ->end()
        ;

        return $node;
    }

    private static function createPingExtension(string $name, string $description): ArrayNodeDefinition
    {
        $treeBuilder = new TreeBuilder($name);
        $node = $treeBuilder->getRootNode();

        $node
            ->info($description.' (alternatively enable by passing a url)')
            ->canBeEnabled()
            ->beforeNormalization()
                ->ifString()
                ->then(function($v) {
                    return [
                        'enabled' => true,
                        'url' => $v,
                        'method' => 'GET',
                        'options' => [],
                    ];
                })
            ->end()
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
