<?php

namespace Zenstruck\ScheduleBundle\Tests\Functional;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\RawMessage;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\EmailHandler;
use Zenstruck\ScheduleBundle\Schedule\ScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EmailTest extends TestCase
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
            ->addTask(MockTask::exception(new \Exception('task 1 exception message'), 'my task 1'))
            ->addTask(MockTask::success('my task 2'))
            ->addTask(MockTask::exception(new \Exception('task 3 exception message'), 'my task 3'))
            ->run()
        ;

        $this->assertSame('webmaster@example.com', $mailer->lastMessage->getFrom()[0]->getAddress());
        $this->assertSame('kevin@example.com', $mailer->lastMessage->getTo()[0]->getAddress());
        $this->assertSame('[Scheduled Failed] 2 tasks failed', $mailer->lastMessage->getSubject());
        $this->assertStringContainsString('2 tasks failed', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('# (Failure 1/2) MockTask: my task 1', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('## Exception', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('Exception: task 1 exception message', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('# (Failure 2/2) MockTask: my task 3', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('## Exception', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('Exception: task 3 exception message', $mailer->lastMessage->getTextBody());
    }

    /**
     * @test
     */
    public function sends_task_failure_email()
    {
        $mailer = $this->createMailer();

        (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($mailer, 'webmaster@example.com', 'kevin@example.com'))
            ->addTask(MockTask::failure('Exit 127: Command not found', 'my task', 'sh: 1: sdsdsd: not found')
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
    }

    /**
     * @test
     */
    public function sends_after_task_email()
    {
        $mailer = $this->createMailer();

        (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($mailer, 'webmaster@example.com', 'kevin@example.com'))
            ->addTask(MockTask::success('my task', 'my task output')->emailAfter())
            ->run()
        ;

        $this->assertSame('webmaster@example.com', $mailer->lastMessage->getFrom()[0]->getAddress());
        $this->assertSame('kevin@example.com', $mailer->lastMessage->getTo()[0]->getAddress());
        $this->assertSame('[Scheduled Task Succeeded] MockTask: my task', $mailer->lastMessage->getSubject());
        $this->assertStringContainsString('Successful', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('## Task Output:', $mailer->lastMessage->getTextBody());
        $this->assertStringContainsString('my task output', $mailer->lastMessage->getTextBody());
    }

    /**
     * @test
     */
    public function provides_helpful_message_if_handler_not_configured()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('To use the email extension you must configure a mailer (config path: "zenstruck_schedule.email_handler").');

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
    public function to_address_must_be_configured()
    {
        $event = (new MockScheduleBuilder())
            ->addHandler(new EmailHandler($this->createMailer()))
            ->addTask(MockTask::failure()->emailOnFailure())
            ->run()
        ;

        $this->assertInstanceOf(\LogicException::class, $event->getResults()[0]->getException());
        $this->assertSame('There is no "To" configured for the email. Either set it when adding the extension or in your configuration (config path: "zenstruck_schedule.email_handler.default_to").', $event->getResults()[0]->getException()->getMessage());
    }

    private function createMailer(): MailerInterface
    {
        return new class() implements MailerInterface {
            /** @var RawMessage */
            public $lastMessage;

            public function send(RawMessage $message, Envelope $envelope = null): void
            {
                $this->lastMessage = $message;
            }
        };
    }
}
