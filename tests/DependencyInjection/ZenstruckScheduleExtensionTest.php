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
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\SelfHandlingHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\SingleServerHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\WithoutOverlappingHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\PingExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\WithoutOverlappingExtension;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunner;
use Zenstruck\ScheduleBundle\Schedule\Task\Runner\CommandTaskRunner;
use Zenstruck\ScheduleBundle\Schedule\Task\Runner\SelfRunningTaskRunner;

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

        $this->assertContainerBuilderHasService(SelfRunningTaskRunner::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(SelfRunningTaskRunner::class, 'schedule.task_runner');

        $this->assertContainerBuilderHasService(ScheduleLoggerSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ScheduleLoggerSubscriber::class, 'kernel.event_subscriber');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ScheduleLoggerSubscriber::class, 'monolog.logger', ['channel' => 'schedule']);

        $this->assertContainerBuilderHasService(ExtensionHandlerRegistry::class);

        $this->assertContainerBuilderHasService(SelfHandlingHandler::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(SelfHandlingHandler::class, 'schedule.extension_handler', ['priority' => -100]);

        $this->assertContainerBuilderHasService(EnvironmentHandler::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(EnvironmentHandler::class, 'schedule.extension_handler');

        $this->assertContainerBuilderHasService(TaskConfigurationSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ScheduleBuilderSubscriber::class, 'kernel.event_subscriber');
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(TaskConfigurationSubscriber::class, 0, []);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(TaskConfigurationSubscriber::class, 1, 'zenstruck_schedule.task_locator');
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
        $this->load(['single_server_handler' => 'my_factory']);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(SingleServerHandler::class, 0, 'my_factory');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(SingleServerHandler::class, 'schedule.extension_handler');
    }

    /**
     * @test
     */
    public function can_configure_without_overlapping_handler_lock_factory()
    {
        $this->load(['without_overlapping_handler' => 'my_factory']);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(WithoutOverlappingHandler::class, 0, 'my_factory');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(WithoutOverlappingHandler::class, 'schedule.extension_handler');
    }

    /**
     * @test
     */
    public function can_configure_ping_handler_http_client()
    {
        $this->load(['ping_handler' => 'my_client']);

        $this->assertContainerBuilderHasServiceDefinitionWithArgument(PingHandler::class, 0, 'my_client');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(PingHandler::class, 'schedule.extension_handler');
    }

    /**
     * @test
     */
    public function can_configure_email_handler()
    {
        $this->load(['email_handler' => [
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
        $this->load(['email_handler' => [
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
    public function can_configure_a_null_task()
    {
        $this->load([
            'tasks' => [
                [
                    'task' => null,
                    'frequency' => '0 * * * *',
                    'description' => 'my task',
                ],
            ],
        ]);

        $config = $this->container->getDefinition(TaskConfigurationSubscriber::class)->getArgument(0)[0];

        $this->assertSame([null], $config['task']);
    }

    /**
     * @test
     */
    public function null_task_must_have_a_description()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "zenstruck_schedule.tasks.0": "null" tasks must have a description.');

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
    public function compound_tasks_must_not_contain_null_tasks()
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('Invalid configuration for path "zenstruck_schedule.tasks.0.task": "null" tasks cannot be added to compound tasks.');

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
    public function can_configure_task_services()
    {
        $this->load([
            'tasks' => [
                [
                    'task' => '@my_task1',
                    'frequency' => '0 0 * * *',
                ],
                [
                    'task' => ['@my_task1', 'my:command', '@my_task2'],
                    'frequency' => '0 0 * * *',
                ],
            ],
        ]);

        $subscriberDefinition = $this->container->getDefinition(TaskConfigurationSubscriber::class);
        $locatorDefinition = $this->container->getDefinition('zenstruck_schedule.task_locator');
        $config = $subscriberDefinition->getArgument(0);

        $this->assertSame(['@my_task1'], $config[0]['task']);
        $this->assertSame(['@my_task1', 'my:command', '@my_task2'], $config[1]['task']);
        $this->assertSame(['my_task1', 'my_task2'], \array_keys($locatorDefinition->getArgument(0)));
        $this->assertSame('my_task1', (string) $locatorDefinition->getArgument(0)['my_task1']);
        $this->assertSame('my_task2', (string) $locatorDefinition->getArgument(0)['my_task2']);
    }

    protected function getContainerExtensions(): array
    {
        return [new ZenstruckScheduleExtension()];
    }
}
