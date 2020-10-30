<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Extension;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\EmailHandler;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EmailExtensionTest extends TestCase
{
    /**
     * @test
     */
    public function sends_schedule_failure_email()
    {
        $mailer = $this->createMailer();

        (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($mailer, 'webmaster@example.com', 'kevin@example.com'))
            ->addBuilder(new class() implements ScheduleBuilder {
                public function buildSchedule(Schedule $schedule): void
                {
                    $schedule->emailOnFailure();
                }
            })
            ->addTask($task1 = MockTask::exception(new \Exception('task 1 exception message'), 'my task 1'))
            ->addTask(MockTask::success('my task 2'))
            ->addTask($task2 = MockTask::exception(new \Exception('task 3 exception message'), 'my task 3'))
            ->run()
        ;

        $this->assertSame('webmaster@example.com', $mailer->lastMessage->getFrom()[0]->getAddress());
        $this->assertSame('kevin@example.com', $mailer->lastMessage->getTo()[0]->getAddress());
        $this->assertSame('[Schedule Failure] 2 tasks failed', $mailer->lastMessage->getSubject());
        $this->assertStringContainsString('2 tasks failed', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('# (Failure 1/2) MockTask: my task 1', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('## Exception', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('Exception: task 1 exception message', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('# (Failure 2/2) MockTask: my task 3', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('## Exception', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('Exception: task 3 exception message', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('Task ID: '.$task1->getId(), $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('Task ID: '.$task2->getId(), $mailer->lastMessage->getTextBody());
    }

    /**
     * @test
     */
    public function sends_schedule_failure_email_with_overrides()
    {
        $mailer = $this->createMailer();

        (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($mailer, 'webmaster@example.com', 'kevin@example.com'))
            ->addBuilder(new class() implements ScheduleBuilder {
                public function buildSchedule(Schedule $schedule): void
                {
                    $schedule->emailOnFailure('to@example.com', 'my subject', function(Email $email) {
                        $email->cc('cc@example.com');
                    });
                }
            })
            ->addTask(MockTask::exception(new \Exception('task 1 exception message'), 'my task 1'))
            ->addTask(MockTask::success('my task 2'))
            ->addTask(MockTask::exception(new \Exception('task 3 exception message'), 'my task 3'))
            ->run()
        ;

        $this->assertSame('to@example.com', $mailer->lastMessage->getTo()[0]->getAddress());
        $this->assertSame('my subject', $mailer->lastMessage->getSubject());
        $this->assertSame('cc@example.com', $mailer->lastMessage->getCc()[0]->getAddress());
    }

    /**
     * @test
     */
    public function sends_schedule_failure_email_with_configured_subject_prefix()
    {
        $mailer = $this->createMailer();

        (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($mailer, 'webmaster@example.com', 'kevin@example.com', '[Acme Inc]'))
            ->addBuilder(new class() implements ScheduleBuilder {
                public function buildSchedule(Schedule $schedule): void
                {
                    $schedule->emailOnFailure();
                }
            })
            ->addTask(MockTask::exception(new \Exception('task 1 exception message'), 'my task 1'))
            ->addTask(MockTask::success('my task 2'))
            ->addTask(MockTask::exception(new \Exception('task 3 exception message'), 'my task 3'))
            ->run()
        ;

        $this->assertSame('[Acme Inc][Schedule Failure] 2 tasks failed', $mailer->lastMessage->getSubject());
    }

    /**
     * @test
     */
    public function sends_task_failure_email()
    {
        $mailer = $this->createMailer();

        (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($mailer, 'webmaster@example.com', 'kevin@example.com'))
            ->addTask($task = MockTask::failure('Exit 127: Command not found', 'my task', 'sh: 1: sdsdsd: not found')
                ->emailOnFailure()
            )
            ->run()
        ;

        $this->assertSame('webmaster@example.com', $mailer->lastMessage->getFrom()[0]->getAddress());
        $this->assertSame('kevin@example.com', $mailer->lastMessage->getTo()[0]->getAddress());
        $this->assertSame('[Scheduled Task Failed] MockTask: my task', $mailer->lastMessage->getSubject());
        $this->assertStringContainsString('Exit 127: Command not found', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('## Task Output:', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('sh: 1: sdsdsd: not found', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('Task ID: '.$task->getId(), $mailer->lastMessage->getTextBody());
    }

    /**
     * @test
     */
    public function sends_task_failure_email_with_overrides()
    {
        $mailer = $this->createMailer();

        (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($mailer, 'webmaster@example.com', 'kevin@example.com'))
            ->addTask(MockTask::failure('Exit 127: Command not found', 'my task', 'sh: 1: sdsdsd: not found')
                ->emailOnFailure('to@example.com', 'my subject', function(Email $email) {
                    $email->cc('cc@example.com');
                })
            )
            ->run()
        ;

        $this->assertSame('to@example.com', $mailer->lastMessage->getTo()[0]->getAddress());
        $this->assertSame('my subject', $mailer->lastMessage->getSubject());
        $this->assertSame('cc@example.com', $mailer->lastMessage->getCc()[0]->getAddress());
    }

    /**
     * @test
     */
    public function sends_task_failure_email_with_configured_subject_prefix()
    {
        $mailer = $this->createMailer();

        (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($mailer, 'webmaster@example.com', 'kevin@example.com', '[Acme Inc]'))
            ->addTask(MockTask::failure('Exit 127: Command not found', 'my task', 'sh: 1: sdsdsd: not found')
                ->emailOnFailure()
            )
            ->run()
        ;

        $this->assertSame('[Acme Inc][Scheduled Task Failed] MockTask: my task', $mailer->lastMessage->getSubject());
    }

    /**
     * @test
     */
    public function sends_after_task_email()
    {
        $mailer = $this->createMailer();

        (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($mailer, 'webmaster@example.com', 'kevin@example.com'))
            ->addTask($task = MockTask::success('my task', 'my task output')->emailAfter())
            ->run()
        ;

        $this->assertSame('webmaster@example.com', $mailer->lastMessage->getFrom()[0]->getAddress());
        $this->assertSame('kevin@example.com', $mailer->lastMessage->getTo()[0]->getAddress());
        $this->assertSame('[Scheduled Task Succeeded] MockTask: my task', $mailer->lastMessage->getSubject());
        $this->assertStringContainsString('Successful', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('## Task Output:', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('my task output', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('Task ID: '.$task->getId(), $mailer->lastMessage->getTextBody());
    }

    /**
     * @test
     */
    public function sends_after_task_email_with_overrides()
    {
        $mailer = $this->createMailer();

        (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($mailer, 'webmaster@example.com', 'kevin@example.com'))
            ->addTask(MockTask::success('my task', 'my task output')
                ->emailAfter('to@example.com', 'my subject', function(Email $email) {
                    $email->cc('cc@example.com');
                })
            )
            ->run()
        ;

        $this->assertSame('to@example.com', $mailer->lastMessage->getTo()[0]->getAddress());
        $this->assertSame('my subject', $mailer->lastMessage->getSubject());
        $this->assertSame('cc@example.com', $mailer->lastMessage->getCc()[0]->getAddress());
    }

    /**
     * @test
     */
    public function sends_after_task_email_with_configured_subject_prefix()
    {
        $mailer = $this->createMailer();

        (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($mailer, 'webmaster@example.com', 'kevin@example.com', '[Acme Inc]'))
            ->addTask(MockTask::success('my task', 'my task output')->emailAfter())
            ->run()
        ;

        $this->assertSame('[Acme Inc][Scheduled Task Succeeded] MockTask: my task', $mailer->lastMessage->getSubject());
    }

    /**
     * @test
     */
    public function provides_helpful_message_if_handler_not_configured()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('To use the email extension you must configure a mailer (config path: "zenstruck_schedule.mailer").');

        (new MockScheduleBuilder())
            ->addBuilder(new class() implements ScheduleBuilder {
                public function buildSchedule(Schedule $schedule): void
                {
                    $schedule->emailOnFailure();
                }
            })
            ->run()
        ;
    }

    /**
     * @test
     */
    public function to_address_must_be_configured_or_passed_to_extension()
    {
        $context = (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($this->createMailer()))
            ->addTask(MockTask::failure()->emailOnFailure())
            ->run()
        ;

        $this->assertInstanceOf(\LogicException::class, $context->getResults()[0]->getException());
        $this->assertSame('There is no "To" configured for the email. Either set it when adding the extension or in your configuration (config path: "zenstruck_schedule.mailer.default_to").', $context->getResults()[0]->getException()->getMessage());
    }

    /**
     * @test
     */
    public function email_shows_if_task_was_force_run()
    {
        $mailer = $this->createMailer();

        (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($mailer, 'webmaster@example.com', 'kevin@example.com'))
            ->addTask($task = MockTask::success('my task', 'my task output')->emailAfter())
            ->run($task->getId())
        ;

        $this->assertStringContainsString('This task was force run', $mailer->lastMessage->getTextBody());
    }

    private function createMailer(): MailerInterface
    {
        return new class() implements MailerInterface {
            /** @var RawMessage */
            public $lastMessage;

            public function send(RawMessage $message, ?Envelope $envelope = null): void
            {
                $this->lastMessage = $message;
            }
        };
    }
}
