<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Extension;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\NoRecipient;
use Symfony\Component\Notifier\Recipient\RecipientInterface;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\NotifierHandler;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 */
final class NotifierExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function sends_schedule_failure_notification()
    {
        $notifier = $this->createNotifier();

        (new MockScheduleBuilder())
            ->addHandler(new NotifierHandler($notifier, ['chat/slack'], 'webmaster@example.com', '123456789'))
            ->addBuilder(new class() implements ScheduleBuilder {
                public function buildSchedule(Schedule $schedule): void
                {
                    $schedule->notifyOnFailure();
                }
            })
            ->addTask($task1 = MockTask::exception(new \Exception('task 1 exception message'), 'my task 1'))
            ->addTask(MockTask::success('my task 2'))
            ->addTask($task2 = MockTask::exception(new \Exception('task 3 exception message'), 'my task 3'))
            ->run()
        ;

        $this->assertSame(['chat/slack'], $notifier->lastNotification->getchannels(new NoRecipient()));
        $this->assertSame('webmaster@example.com', $notifier->recipients[0]->getEmail());
        $this->assertSame('123456789', $notifier->recipients[0]->getPhone());
        $this->assertSame('[Schedule Failure] 2 tasks failed', $notifier->lastNotification->getSubject());
        $this->assertStringContainsString('2 tasks failed', $notifier->lastNotification->getContent());
        $this->assertStringContainsString('# (Failure 1/2) MockTask: my task 1', $notifier->lastNotification->getContent());
        $this->assertStringNotContainsString('## Exception', $notifier->lastNotification->getContent());
        $this->assertStringContainsString('Exception: task 1 exception message', $notifier->lastNotification->getContent());
        $this->assertStringContainsString('# (Failure 2/2) MockTask: my task 3', $notifier->lastNotification->getContent());
        $this->assertStringNotContainsString('## Exception', $notifier->lastNotification->getContent());
        $this->assertStringContainsString('Exception: task 3 exception message', $notifier->lastNotification->getContent());
        $this->assertStringContainsString('Task ID: '.$task1->getId(), $notifier->lastNotification->getContent());
        $this->assertStringContainsString('Task ID: '.$task2->getId(), $notifier->lastNotification->getContent());
    }

    /**
     * @test
     */
    public function sends_schedule_failure_notification_with_overrides()
    {
        $notifier = $this->createNotifier();

        (new MockScheduleBuilder())
            ->addHandler(new NotifierHandler($notifier, ['chat/slack'], 'webmaster@example.com', '127.0.0.1'))
            ->addBuilder(new class() implements ScheduleBuilder {
                public function buildSchedule(Schedule $schedule): void
                {
                    $schedule->notifyOnFailure('teams', null, null, 'my subject', function(Notification $notification) {
                        $notification->emoji('alert');
                    });
                }
            })
            ->addTask(MockTask::exception(new \Exception('task 1 exception message'), 'my task 1'))
            ->addTask(MockTask::success('my task 2'))
            ->addTask(MockTask::exception(new \Exception('task 3 exception message'), 'my task 3'))
            ->run()
        ;

        $this->assertSame(['teams'], $notifier->lastNotification->getChannels(new NoRecipient()));
        $this->assertSame('my subject', $notifier->lastNotification->getSubject());
        $this->assertSame('alert', $notifier->lastNotification->getEmoji());
    }

    /**
     * @test
     */
    public function sends_schedule_failure_notification_with_configured_subject_prefix()
    {
        $notifier = $this->createNotifier();

        (new MockScheduleBuilder())
            ->addHandler(new NotifierHandler($notifier, ['chat/slack'], 'webmaster@example.com', 'kevin@example.com', '[Acme Inc]'))
            ->addBuilder(new class() implements ScheduleBuilder {
                public function buildSchedule(Schedule $schedule): void
                {
                    $schedule->notifyOnFailure();
                }
            })
            ->addTask(MockTask::exception(new \Exception('task 1 exception message'), 'my task 1'))
            ->addTask(MockTask::success('my task 2'))
            ->addTask(MockTask::exception(new \Exception('task 3 exception message'), 'my task 3'))
            ->run()
        ;

        $this->assertSame('[Acme Inc][Schedule Failure] 2 tasks failed', $notifier->lastNotification->getSubject());
    }

    /**
     * @test
     */
    public function sends_task_failure_notification()
    {
        $notifier = $this->createNotifier();

        (new MockScheduleBuilder())
            ->addHandler(new NotifierHandler($notifier, ['chat/slack'], 'webmaster@example.com', '123456789'))
            ->addTask($task = MockTask::failure('Exit 127: Command not found', 'my task', 'sh: 1: sdsdsd: not found')
                ->notifyOnFailure(),
            )
            ->run()
        ;

        $this->assertcount(1, $notifier->recipients);
        $this->assertSame('webmaster@example.com', $notifier->recipients[0]->getEmail());
        $this->assertSame('123456789', $notifier->recipients[0]->getPhone());
        $this->assertSame('[Scheduled Task Failed] MockTask: my task', $notifier->lastNotification->getSubject());
        $this->assertStringContainsString('Exit 127: Command not found', $notifier->lastNotification->getContent());
        $this->assertStringContainsString('## Task Output:', $notifier->lastNotification->getContent());
        $this->assertStringContainsString('sh: 1: sdsdsd: not found', $notifier->lastNotification->getContent());
        $this->assertStringContainsString('Task ID: '.$task->getId(), $notifier->lastNotification->getContent());
    }

    /**
     * @test
     */
    public function sends_task_failure_notification_with_overrides()
    {
        $notifier = $this->createNotifier();

        (new MockScheduleBuilder())
            ->addHandler(new NotifierHandler($notifier, ['chat/slack'], 'webmaster@example.com', '123456789'))
            ->addTask(MockTask::failure('Exit 127: Command not found', 'my task', 'sh: 1: sdsdsd: not found')
                ->notifyOnFailure('teams', 'to@example.com', null, 'my subject', function(Notification $notification) {
                    $notification->emoji('alert');
                }),
            )
            ->run()
        ;

        $this->assertSame(['teams'], $notifier->lastNotification->getChannels(new NoRecipient()));
        $this->assertSame('my subject', $notifier->lastNotification->getSubject());
        $this->assertSame('alert', $notifier->lastNotification->getEmoji());
    }

    /**
     * @test
     */
    public function sends_task_failure_notification_with_configured_subject_prefix()
    {
        $notifier = $this->createNotifier();

        (new MockScheduleBuilder())
            ->addHandler(new NotifierHandler($notifier, ['chat/slack'], null, null, '[Acme Inc]'))
            ->addTask(MockTask::failure('Exit 127: Command not found', 'my task', 'sh: 1: sdsdsd: not found')
                ->notifyOnFailure(),
            )
            ->run()
        ;

        $this->assertSame('[Acme Inc][Scheduled Task Failed] MockTask: my task', $notifier->lastNotification->getSubject());
    }

    /**
     * @test
     */
    public function sends_after_task_notification()
    {
        $notifier = $this->createNotifier();

        (new MockScheduleBuilder())
            ->addHandler(new NotifierHandler($notifier, ['chat/slack'], 'webmaster@example.com'))
            ->addTask($task = MockTask::success('my task', 'my task output')->notifyAfter())
            ->run()
        ;

        $this->assertSame(['chat/slack'], $notifier->lastNotification->getchannels(new NoRecipient()));
        $this->assertSame('webmaster@example.com', $notifier->recipients[0]->getEmail());
        $this->assertSame('[Scheduled Task Succeeded] MockTask: my task', $notifier->lastNotification->getSubject());
        $this->assertStringContainsString('Successful', $notifier->lastNotification->getContent());
        $this->assertStringContainsString('## Task Output:', $notifier->lastNotification->getContent());
        $this->assertStringContainsString('my task output', $notifier->lastNotification->getContent());
        $this->assertStringContainsString('Task ID: '.$task->getId(), $notifier->lastNotification->getContent());
    }

    /**
     * @test
     */
    public function sends_after_task_notification_with_overrides()
    {
        $notifier = $this->createNotifier();

        (new MockScheduleBuilder())
            ->addHandler(new NotifierHandler($notifier, ['chat/slack'], 'webmaster@example.com', '123456789'))
            ->addTask(MockTask::success('my task', 'my task output')
                ->notifyAfter(['teams'], 'to@example.com', '987654321', 'my subject', function(Notification $notification) {
                    $notification->emoji('alert');
                }),
            )
            ->run()
        ;

        $this->assertSame(['teams'], $notifier->lastNotification->getChannels(new NoRecipient()));
        $this->assertSame('to@example.com', $notifier->recipients[0]->getEmail());
        $this->assertSame('987654321', $notifier->recipients[0]->getPhone());
        $this->assertSame('my subject', $notifier->lastNotification->getSubject());
        $this->assertSame('alert', $notifier->lastNotification->getEmoji());
    }

    /**
     * @test
     */
    public function sends_after_task_notification_with_configured_subject_prefix()
    {
        $notifier = $this->createNotifier();

        (new MockScheduleBuilder())
            ->addHandler(new NotifierHandler($notifier, ['chat/slack'], 'webmaster@example.com', '123456789', '[Acme Inc]'))
            ->addTask(MockTask::success('my task', 'my task output')->notifyAfter())
            ->run()
        ;

        $this->assertSame('[Acme Inc][Scheduled Task Succeeded] MockTask: my task', $notifier->lastNotification->getSubject());
    }

    /**
     * @test
     */
    public function provides_helpful_message_if_handler_not_configured()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('To use the notifier extension you must configure a notifier (config path: "zenstruck_schedule.notifier").');

        (new MockScheduleBuilder())
            ->addBuilder(new class() implements ScheduleBuilder {
                public function buildSchedule(Schedule $schedule): void
                {
                    $schedule->notifyOnFailure();
                }
            })
            ->run()
        ;
    }

    /**
     * @test
     */
    public function channel_must_be_configured_or_passed_to_extension()
    {
        $context = (new MockScheduleBuilder())
            ->addHandler(new NotifierHandler($this->createNotifier()))
            ->addTask(MockTask::failure()->notifyOnFailure())
            ->run()
        ;

        $this->assertInstanceOf(\LogicException::class, $context->getResults()[0]->getException());
        $this->assertSame('There is no "Channel" configured for the notification. Either set it when adding the extension or in your configuration (config path: "zenstruck_schedule.notifier.default_channel").', $context->getResults()[0]->getException()->getMessage());
    }

    /**
     * @test
     */
    public function notification_shows_if_task_was_force_run()
    {
        $notifier = $this->createNotifier();

        (new MockScheduleBuilder())
            ->addHandler(new NotifierHandler($notifier, ['chat/slack'], 'webmaster@example.com', '1234567890'))
            ->addTask($task = MockTask::success('my task', 'my task output')->notifyAfter())
            ->run($task->getId())
        ;

        $this->assertStringContainsString('This task was force run', $notifier->lastNotification->getContent());
    }

    private function createNotifier(): NotifierInterface
    {
        return new class() implements NotifierInterface {
            /** @var Notification */
            public $lastNotification;

            /** @var RecipientInterface[] */
            public $recipients = [];

            public function send(Notification $notification, RecipientInterface ...$recipients): void
            {
                $this->lastNotification = $notification;
                $this->recipients = $recipients;
            }
        };
    }
}
