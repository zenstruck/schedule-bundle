<?php

namespace Zenstruck\ScheduleBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\Argument\TaggedIteratorArgument;
use Zenstruck\ScheduleBundle\Command\ScheduleListCommand;
use Zenstruck\ScheduleBundle\Command\ScheduleRunCommand;
use Zenstruck\ScheduleBundle\DependencyInjection\ZenstruckScheduleExtension;
use Zenstruck\ScheduleBundle\EventListener\ScheduleBuilderSubscriber;
use Zenstruck\ScheduleBundle\EventListener\ScheduleExtensionSubscriber;
use Zenstruck\ScheduleBundle\EventListener\ScheduleLoggerSubscriber;
use Zenstruck\ScheduleBundle\EventListener\ScheduleTimezoneSubscriber;
use Zenstruck\ScheduleBundle\EventListener\SelfSchedulingCommandSubscriber;
use Zenstruck\ScheduleBundle\EventListener\TaskConfigurationSubscriber;
use Zenstruck\ScheduleBundle\Schedule\Extension\EmailExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\EnvironmentExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandlerRegistry;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\EmailHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\EnvironmentHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\PingHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\SingleServerHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\WithoutOverlappingHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\PingExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\WithoutOverlappingExtension;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunner;
use Zenstruck\ScheduleBundle\Schedule\Task\Runner\CallbackTaskRunner;
use Zenstruck\ScheduleBundle\Schedule\Task\Runner\CommandTaskRunner;
use Zenstruck\ScheduleBundle\Schedule\Task\Runner\PingTaskRunner;
use Zenstruck\ScheduleBundle\Schedule\Task\Runner\ProcessTaskRunner;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ZenstruckScheduleExtensionTest extends AbstractExtensionTestCase
{
    /**
     * @test
     */
    public function empty_config_loads_default_services()
    {
        $this->load([]);

        $this->assertContainerBuilderHasService(ScheduleListCommand::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ScheduleListCommand::class, 'console.command');

        $this->assertContainerBuilderHasService(ScheduleRunCommand::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ScheduleRunCommand::class, 'console.command');

        $this->assertContainerBuilderHasService(ScheduleRunner::class);

        $this->assertContainerBuilderHasService(ScheduleBuilderSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ScheduleBuilderSubscriber::class, 'kernel.event_subscriber');

        $this->assertContainerBuilderHasService(ScheduleExtensionSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ScheduleExtensionSubscriber::class, 'kernel.event_subscriber');

        $this->assertContainerBuilderHasService(SelfSchedulingCommandSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(SelfSchedulingCommandSubscriber::class, 'kernel.event_subscriber');

        $this->assertContainerBuilderHasService(CommandTaskRunner::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CommandTaskRunner::class, 'schedule.task_runner');

        $this->assertContainerBuilderHasService(ProcessTaskRunner::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ProcessTaskRunner::class, 'schedule.task_runner');

        $this->assertContainerBuilderHasService(CallbackTaskRunner::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CallbackTaskRunner::class, 'schedule.task_runner');

        $this->assertContainerBuilderHasService(ScheduleLoggerSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ScheduleLoggerSubscriber::class, 'kernel.event_subscriber');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ScheduleLoggerSubscriber::class, 'monolog.logger', ['channel' => 'schedule']);

        $this->assertContainerBuilderHasService(ExtensionHandlerRegistry::class);

        $this->assertContainerBuilderHasService(EnvironmentHandler::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(EnvironmentHandler::class, 'schedule.extension_handler');

        $this->assertContainerBuilderHasService(TaskConfigurationSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ScheduleBuilderSubscriber::class, 'kernel.event_subscriber');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(TaskConfigurationSubscriber::class, 0, []);

        $this->assertContainerBuilderHasServiceDefinitionWithTag(PingHandler::class, 'schedule.extension_handler');
        $this->assertEmpty($this->container->findDefinition(PingHandler::class)->getArguments());

        $this->assertContainerBuilderHasServiceDefinitionWithTag(PingTaskRunner::class, 'schedule.task_runner');
        $this->assertEmpty($this->container->findDefinition(PingTaskRunner::class)->getArguments());

        $this->assertContainerBuilderHasServiceDefinitionWithTag(WithoutOverlappingHandler::class, 'schedule.extension_handler');
        $this->assertEmpty($this->container->findDefinition(WithoutOverlappingHandler::class)->getArguments());
    }

    /**
     * @test
     */
    public function can_configure_default_timezone()
    {
        $this->load(['timezone' => 'UTC']);

        $this->assertContainerBuilderHasService(ScheduleTimezoneSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(ScheduleTimezoneSubscriber::class, 0, 'UTC');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ScheduleTimezoneSubscriber::class, 'kernel.event_subscriber');
    }

    /**
     * @test
     */
    public function schedule_timezone_must_be_valid()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "zenstruck_schedule.timezone": Timezone "invalid" is not available');

        $this->load(['timezone' => 'invalid']);
    }

    /**
     * @test
     */
    public function can_configure_single_server_lock_factory()
    {
        $this->load(['single_server_lock_factory' => 'my_factory']);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(SingleServerHandler::class, 0, 'my_factory');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(SingleServerHandler::class, 'schedule.extension_handler');
    }

    /**
     * @test
     */
    public function can_configure_without_overlapping_handler_lock_factory()
    {
        $this->load(['without_overlapping_lock_factory' => 'my_factory']);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(WithoutOverlappingHandler::class, 0, 'my_factory');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(WithoutOverlappingHandler::class, 'schedule.extension_handler');
    }

    /**
     * @test
     */
    public function can_configure_http_client()
    {
        $this->load(['http_client' => 'my_client']);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(PingHandler::class, 0, 'my_client');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(PingHandler::class, 'schedule.extension_handler');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(PingTaskRunner::class, 0, 'my_client');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(PingTaskRunner::class, 'schedule.task_runner');
    }

    /**
     * @test
     */
    public function can_configure_email_handler()
    {
        $this->load(['mailer' => [
            'service' => 'my_mailer',
            'default_from' => 'from@example.com',
            'default_to' => 'to@example.com',
            'subject_prefix' => '[Acme Inc]',
        ]]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(EmailHandler::class, 0, 'my_mailer');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(EmailHandler::class, 'schedule.extension_handler');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(EmailHandler::class, 1, 'from@example.com');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(EmailHandler::class, 2, 'to@example.com');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(EmailHandler::class, 3, '[Acme Inc]');
    }

    /**
     * @test
     */
    public function minimum_email_handler_configuration()
    {
        $this->load(['mailer' => [
            'service' => 'my_mailer',
        ]]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(EmailHandler::class, 0, 'my_mailer');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(EmailHandler::class, 'schedule.extension_handler');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(EmailHandler::class, 1, null);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(EmailHandler::class, 2, null);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(EmailHandler::class, 3, null);
    }

    /**
     * @test
     */
    public function can_add_schedule_environment_as_string()
    {
        $this->load(['schedule_extensions' => [
            'environments' => 'prod',
        ]]);

        $this->assertContainerBuilderHasService('zenstruck_schedule.extension.environments', EnvironmentExtension::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument('zenstruck_schedule.extension.environments', 0, ['prod']);
        $this->assertContainerBuilderHasServiceDefinitionWithTag('zenstruck_schedule.extension.environments', 'schedule.extension');

        $extensionIterator = $this->container->getDefinition(ScheduleExtensionSubscriber::class)->getArgument(0);

        $this->assertInstanceOf(TaggedIteratorArgument::class, $extensionIterator);
        $this->assertSame('schedule.extension', $extensionIterator->getTag());
    }

    /**
     * @test
     */
    public function can_add_schedule_environment_as_array()
    {
        $this->load(['schedule_extensions' => [
            'environments' => ['prod', 'stage'],
        ]]);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument('zenstruck_schedule.extension.environments', 0, ['prod', 'stage']);
    }

    /**
     * @test
     */
    public function can_enable_single_server_schedule_extension()
    {
        $this->load(['schedule_extensions' => [
            'on_single_server' => null,
        ]]);

        $this->assertContainerBuilderHasService('zenstruck_schedule.extension.on_single_server', SingleServerExtension::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag('zenstruck_schedule.extension.on_single_server', 'schedule.extension');

        $extensionIterator = $this->container->getDefinition(ScheduleExtensionSubscriber::class)->getArgument(0);

        $this->assertInstanceOf(TaggedIteratorArgument::class, $extensionIterator);
        $this->assertSame('schedule.extension', $extensionIterator->getTag());
    }

    /**
     * @test
     */
    public function can_enable_email_on_failure_schedule_extension()
    {
        $this->load(['schedule_extensions' => [
            'email_on_failure' => [
                'to' => 'to@example.com',
                'subject' => 'my subject',
            ],
        ]]);

        $this->assertContainerBuilderHasService('zenstruck_schedule.extension.email_on_failure', EmailExtension::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag('zenstruck_schedule.extension.email_on_failure', 'schedule.extension');

        $definition = $this->container->getDefinition('zenstruck_schedule.extension.email_on_failure');

        $this->assertSame([EmailExtension::class, 'scheduleFailure'], $definition->getFactory());
        $this->assertSame(['to@example.com', 'my subject'], $definition->getArguments());

        $extensionIterator = $this->container->getDefinition(ScheduleExtensionSubscriber::class)->getArgument(0);

        $this->assertInstanceOf(TaggedIteratorArgument::class, $extensionIterator);
        $this->assertSame('schedule.extension', $extensionIterator->getTag());
    }

    /**
     * @test
     * @dataProvider pingScheduleExtensionProvider
     */
    public function can_enable_ping_schedule_extensions($key, $method)
    {
        $this->load(['schedule_extensions' => [
            $key => [
                'url' => 'example.com',
            ],
        ]]);

        $this->assertContainerBuilderHasService('zenstruck_schedule.extension.'.$key, PingExtension::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag('zenstruck_schedule.extension.'.$key, 'schedule.extension');

        $definition = $this->container->getDefinition('zenstruck_schedule.extension.'.$key);

        $this->assertSame([PingExtension::class, $method], $definition->getFactory());
        $this->assertSame(['example.com', 'GET', []], $definition->getArguments());

        $extensionIterator = $this->container->getDefinition(ScheduleExtensionSubscriber::class)->getArgument(0);

        $this->assertInstanceOf(TaggedIteratorArgument::class, $extensionIterator);
        $this->assertSame('schedule.extension', $extensionIterator->getTag());
    }

    public static function pingScheduleExtensionProvider()
    {
        return [
            ['ping_before', 'scheduleBefore'],
            ['ping_after', 'scheduleAfter'],
            ['ping_on_success', 'scheduleSuccess'],
            ['ping_on_failure', 'scheduleFailure'],
        ];
    }

    /**
     * @test
     */
    public function minimum_task_configuration()
    {
        $this->load([
            'tasks' => [
                [
                    'task' => 'my:command',
                    'frequency' => '0 * * * *',
                ],
            ],
        ]);

        $config = $this->container->getDefinition(TaskConfigurationSubscriber::class)->getArgument(0)[0];

        $this->assertSame(['my:command'], $config['task']);
        $this->assertSame('0 * * * *', $config['frequency']);
        $this->assertNull($config['description']);
        $this->assertNull($config['timezone']);
        $this->assertFalse($config['without_overlapping']['enabled']);
        $this->assertFalse($config['only_between']['enabled']);
        $this->assertFalse($config['unless_between']['enabled']);
        $this->assertFalse($config['ping_before']['enabled']);
        $this->assertFalse($config['ping_after']['enabled']);
        $this->assertFalse($config['ping_on_success']['enabled']);
        $this->assertFalse($config['ping_on_failure']['enabled']);
        $this->assertFalse($config['email_after']['enabled']);
        $this->assertFalse($config['email_on_failure']['enabled']);
    }

    /**
     * @test
     */
    public function can_configure_a_compound_task()
    {
        $this->load([
            'tasks' => [
                [
                    'task' => ['my:command', 'bash:/my-script'],
                    'frequency' => '0 * * * *',
                ],
            ],
        ]);

        $config = $this->container->getDefinition(TaskConfigurationSubscriber::class)->getArgument(0)[0];

        $this->assertSame(['my:command', 'bash:/my-script'], $config['task']);
    }

    /**
     * @test
     */
    public function can_configure_a_compound_task_with_descriptions()
    {
        $this->load([
            'tasks' => [
                [
                    'task' => [
                        'task1' => 'my:command',
                        'task2' => 'bash:/my-script',
                    ],
                    'frequency' => '0 * * * *',
                ],
            ],
        ]);

        $config = $this->container->getDefinition(TaskConfigurationSubscriber::class)->getArgument(0)[0];

        $this->assertSame(['task1' => 'my:command', 'task2' => 'bash:/my-script'], $config['task']);
    }

    /**
     * @test
     */
    public function compound_tasks_cannot_be_an_empty_array()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "zenstruck_schedule.tasks.0.task" should have at least 1 element(s) defined.');

        $this->load([
            'tasks' => [
                [
                    'task' => [],
                    'frequency' => 'invalid',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function task_is_required()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The child node "task" at path "zenstruck_schedule.tasks.0" must be configured.');

        $this->load([
            'tasks' => [
                [
                    'frequency' => '0 * * * *',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function task_cannot_be_null()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "zenstruck_schedule.tasks.0.task": Task cannot be empty value.');

        $this->load([
            'tasks' => [
                [
                    'task' => null,
                    'frequency' => '0 * * * *',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function task_cannot_be_empty()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "zenstruck_schedule.tasks.0.task": Task cannot be empty value.');

        $this->load([
            'tasks' => [
                [
                    'task' => '',
                    'frequency' => '0 * * * *',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function compound_tasks_must_not_contain_null_values()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "zenstruck_schedule.tasks.0.task": Task cannot be empty value.');

        $this->load([
            'tasks' => [
                [
                    'task' => ['my:command', null],
                    'frequency' => 'invalid',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function task_frequency_is_required()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The child node "frequency" at path "zenstruck_schedule.tasks.0" must be configured.');

        $this->load([
            'tasks' => [
                [
                    'task' => 'my:command',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function task_frequency_cannot_be_null()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The path "zenstruck_schedule.tasks.0.frequency" cannot contain an empty value, but got null.');

        $this->load([
            'tasks' => [
                [
                    'task' => 'my:command',
                    'frequency' => null,
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function task_frequency_must_be_valid()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "zenstruck_schedule.tasks.0.frequency": "invalid" is an invalid cron expression.');

        $this->load([
            'tasks' => [
                [
                    'task' => 'my:command',
                    'frequency' => 'invalid',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function can_use_extended_frequency_expression()
    {
        $this->load([
            'tasks' => [
                [
                    'task' => 'my:command',
                    'frequency' => '@daily',
                ],
            ],
        ]);

        $config = $this->container->getDefinition(TaskConfigurationSubscriber::class)->getArgument(0)[0];

        $this->assertSame('@daily', $config['frequency']);
    }

    /**
     * @test
     */
    public function can_use_hashed_frequency_expression()
    {
        $this->load([
            'tasks' => [
                [
                    'task' => 'my:command',
                    'frequency' => 'H H * * *',
                ],
            ],
        ]);

        $config = $this->container->getDefinition(TaskConfigurationSubscriber::class)->getArgument(0)[0];

        $this->assertSame('H H * * *', $config['frequency']);
    }

    /**
     * @test
     */
    public function can_use_frequency_alias()
    {
        $this->load([
            'tasks' => [
                [
                    'task' => 'my:command',
                    'frequency' => '#midnight',
                ],
            ],
        ]);

        $config = $this->container->getDefinition(TaskConfigurationSubscriber::class)->getArgument(0)[0];

        $this->assertSame('#midnight', $config['frequency']);
    }

    /**
     * @test
     */
    public function full_task_configuration()
    {
        $this->load([
            'tasks' => [
                [
                    'task' => [
                        'my:command --option',
                        'another:command',
                    ],
                    'frequency' => '0 0 * * *',
                    'description' => 'my description',
                    'timezone' => 'UTC',
                    'without_overlapping' => null,
                    'only_between' => [
                        'start' => 9,
                        'end' => 17,
                    ],
                    'unless_between' => [
                        'start' => 12,
                        'end' => '13:30',
                    ],
                    'ping_before' => [
                        'url' => 'https://example.com/before',
                    ],
                    'ping_after' => [
                        'url' => 'https://example.com/after',
                    ],
                    'ping_on_success' => [
                        'url' => 'https://example.com/success',
                    ],
                    'ping_on_failure' => [
                        'url' => 'https://example.com/failure',
                        'method' => 'POST',
                    ],
                    'email_after' => null,
                    'email_on_failure' => [
                        'to' => 'sales@example.com',
                        'subject' => 'my subject',
                    ],
                ],
            ],
        ]);

        $config = $this->container->getDefinition(TaskConfigurationSubscriber::class)->getArgument(0)[0];

        $this->assertSame(['my:command --option', 'another:command'], $config['task']);
        $this->assertSame('0 0 * * *', $config['frequency']);
        $this->assertSame('my description', $config['description']);
        $this->assertSame('UTC', $config['timezone']);
        $this->assertTrue($config['without_overlapping']['enabled']);
        $this->assertSame(WithoutOverlappingExtension::DEFAULT_TTL, $config['without_overlapping']['ttl']);
        $this->assertTrue($config['only_between']['enabled']);
        $this->assertSame(9, $config['only_between']['start']);
        $this->assertSame(17, $config['only_between']['end']);
        $this->assertTrue($config['unless_between']['enabled']);
        $this->assertSame(12, $config['unless_between']['start']);
        $this->assertSame('13:30', $config['unless_between']['end']);
        $this->assertTrue($config['ping_before']['enabled']);
        $this->assertSame('https://example.com/before', $config['ping_before']['url']);
        $this->assertSame('GET', $config['ping_before']['method']);
        $this->assertTrue($config['ping_after']['enabled']);
        $this->assertSame('https://example.com/after', $config['ping_after']['url']);
        $this->assertSame('GET', $config['ping_after']['method']);
        $this->assertTrue($config['ping_on_success']['enabled']);
        $this->assertSame('https://example.com/success', $config['ping_on_success']['url']);
        $this->assertSame('GET', $config['ping_on_success']['method']);
        $this->assertTrue($config['ping_on_failure']['enabled']);
        $this->assertSame('https://example.com/failure', $config['ping_on_failure']['url']);
        $this->assertSame('POST', $config['ping_on_failure']['method']);
        $this->assertTrue($config['email_after']['enabled']);
        $this->assertNull($config['email_after']['to']);
        $this->assertNull($config['email_after']['subject']);
        $this->assertTrue($config['email_on_failure']['enabled']);
        $this->assertSame('sales@example.com', $config['email_on_failure']['to']);
        $this->assertSame('my subject', $config['email_on_failure']['subject']);
    }

    /**
     * @test
     */
    public function email_and_ping_configuration_can_be_shortened()
    {
        $this->load([
            'tasks' => [
                [
                    'task' => 'my:command --option',
                    'frequency' => '0 0 * * *',
                    'ping_after' => 'https://example.com/after',
                    'email_after' => 'sales@example.com',
                ],
            ],
        ]);

        $config = $this->container->getDefinition(TaskConfigurationSubscriber::class)->getArgument(0)[0];

        $this->assertTrue($config['ping_after']['enabled']);
        $this->assertSame('https://example.com/after', $config['ping_after']['url']);
        $this->assertSame('GET', $config['ping_after']['method']);
        $this->assertSame([], $config['ping_after']['options']);

        $this->assertTrue($config['email_after']['enabled']);
        $this->assertSame('sales@example.com', $config['email_after']['to']);
        $this->assertNull($config['email_after']['subject']);
    }

    /**
     * @test
     */
    public function between_and_unless_between_config_can_be_shortened()
    {
        $this->load([
            'tasks' => [
                [
                    'task' => 'my:command --option',
                    'frequency' => '0 0 * * *',
                    'only_between' => '9-17',
                    'unless_between' => '11:30-13:15',
                ],
            ],
        ]);

        $config = $this->container->getDefinition(TaskConfigurationSubscriber::class)->getArgument(0)[0];

        $this->assertTrue($config['only_between']['enabled']);
        $this->assertSame('9', $config['only_between']['start']);
        $this->assertSame('17', $config['only_between']['end']);

        $this->assertTrue($config['unless_between']['enabled']);
        $this->assertSame('11:30', $config['unless_between']['start']);
        $this->assertSame('13:15', $config['unless_between']['end']);
    }

    /**
     * @test
     */
    public function task_config_must_be_an_array(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid type for path "zenstruck_schedule.tasks.0.config"');

        $this->load([
            'tasks' => [
                [
                    'task' => 'my:command',
                    'frequency' => '0 * * * *',
                    'config' => 'not an array',
                ],
            ],
        ]);
    }

    protected function getContainerExtensions(): array
    {
        return [new ZenstruckScheduleExtension()];
    }
}
