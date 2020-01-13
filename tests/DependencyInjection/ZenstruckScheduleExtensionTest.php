<?php

namespace Zenstruck\ScheduleBundle\Tests\DependencyInjection;

use Matthias\SymfonyDependencyInjectionTest\PhpUnit\AbstractExtensionTestCase;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Zenstruck\ScheduleBundle\Command\ScheduleListCommand;
use Zenstruck\ScheduleBundle\Command\ScheduleRunCommand;
use Zenstruck\ScheduleBundle\DependencyInjection\ZenstruckScheduleExtension;
use Zenstruck\ScheduleBundle\EventListener\ConfigureScheduleSubscriber;
use Zenstruck\ScheduleBundle\EventListener\LogScheduleSubscriber;
use Zenstruck\ScheduleBundle\EventListener\ScheduleBuilderSubscriber;
use Zenstruck\ScheduleBundle\EventListener\SelfSchedulingSubscriber;
use Zenstruck\ScheduleBundle\EventListener\TimezoneSubscriber;
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
        $this->assertContainerBuilderHasService(ConfigureScheduleSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(ConfigureScheduleSubscriber::class, 'kernel.event_subscriber');
        $this->assertContainerBuilderHasService(SelfSchedulingSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(SelfSchedulingSubscriber::class, 'kernel.event_subscriber');
        $this->assertContainerBuilderHasService(CommandTaskRunner::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(CommandTaskRunner::class, 'schedule.task_runner');
        $this->assertContainerBuilderHasService(SelfRunningTaskRunner::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(SelfRunningTaskRunner::class, 'schedule.task_runner');
        $this->assertContainerBuilderHasService(LogScheduleSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(LogScheduleSubscriber::class, 'kernel.event_subscriber');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(LogScheduleSubscriber::class, 'monolog.logger', ['channel' => 'schedule']);
        $this->assertContainerBuilderHasService(ExtensionHandlerRegistry::class);
        $this->assertContainerBuilderHasService(SelfHandlingHandler::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(SelfHandlingHandler::class, 'schedule.extension_handler', ['priority' => -100]);
        $this->assertContainerBuilderHasService(EnvironmentHandler::class);
        $this->assertContainerBuilderHasServiceDefinitionWithTag(EnvironmentHandler::class, 'schedule.extension_handler');
    }

    /**
     * @test
     */
    public function can_configure_default_timezone()
    {
        $this->load(['timezone' => 'UTC']);

        $this->assertContainerBuilderHasService(TimezoneSubscriber::class);
        $this->assertContainerBuilderHasServiceDefinitionWithArgument(TimezoneSubscriber::class, 0, 'UTC');
        $this->assertContainerBuilderHasServiceDefinitionWithTag(TimezoneSubscriber::class, 'kernel.event_subscriber');
    }

    /**
     * @test
     */
    public function timezone_must_be_valid()
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
        $this->assertContainerBuilderHasServiceDefinitionWithTag('zenstruck_schedule.extension.environments', 'schedule.configured_extension');
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
        $this->assertContainerBuilderHasServiceDefinitionWithTag('zenstruck_schedule.extension.on_single_server', 'schedule.configured_extension');
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
        $this->assertContainerBuilderHasServiceDefinitionWithTag('zenstruck_schedule.extension.email_on_failure', 'schedule.configured_extension');

        $definition = $this->container->getDefinition('zenstruck_schedule.extension.email_on_failure');

        $this->assertSame([EmailExtension::class, 'scheduleFailure'], $definition->getFactory());
        $this->assertSame(['to@example.com', 'my subject'], $definition->getArguments());
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
        $this->assertContainerBuilderHasServiceDefinitionWithTag('zenstruck_schedule.extension.'.$key, 'schedule.configured_extension');

        $definition = $this->container->getDefinition('zenstruck_schedule.extension.'.$key);

        $this->assertSame([PingExtension::class, $method], $definition->getFactory());
        $this->assertSame(['example.com', 'GET', []], $definition->getArguments());
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

    protected function getContainerExtensions(): array
    {
        return [new ZenstruckScheduleExtension()];
    }
}
