<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Zenstruck\ScheduleBundle\Event\AfterScheduleEvent;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Extension\EmailExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EmailHandler extends ExtensionHandler
{
    private $mailer;
    private $defaultFrom;
    private $defaultTo;
    private $subjectPrefix;

    public function __construct(MailerInterface $mailer, string $defaultFrom = null, string $defaultTo = null, string $subjectPrefix = null)
    {
        $this->mailer = $mailer;
        $this->defaultFrom = $defaultFrom;
        $this->defaultTo = $defaultTo;
        $this->subjectPrefix = $subjectPrefix;
    }

    /**
     * @param EmailExtension $extension
     */
    public function onScheduleFailure(AfterScheduleEvent $event, Extension $extension): void
    {
        if ($extension->isHook(Extension::SCHEDULE_FAILURE)) {
            $this->sendScheduleEmail($event, $extension);
        }
    }

    /**
     * @param EmailExtension $extension
     */
    public function afterTask(AfterTaskEvent $event, Extension $extension): void
    {
        if ($extension->isHook(Extension::TASK_AFTER)) {
            $this->sendTaskEmail($extension, $event->getResult());
        }
    }

    /**
     * @param EmailExtension $extension
     */
    public function onTaskFailure(AfterTaskEvent $event, Extension $extension): void
    {
        if ($extension->isHook(Extension::TASK_FAILURE)) {
            $this->sendTaskEmail($extension, $event->getResult());
        }
    }

    public function supports(Extension $extension): bool
    {
        return $extension instanceof EmailExtension;
    }

    private function sendScheduleEmail(AfterScheduleEvent $event, EmailExtension $extension): void
    {
        $email = $this->emailHeaderFor($extension);
        $failureCount = \count($event->getFailures());
        $summary = \sprintf('%d task%s failed', $failureCount, $failureCount > 1 ? 's' : '');
        $text = $summary;

        $email->priority(Email::PRIORITY_HIGHEST);
        $this->prefixSubject($email, "[Scheduled Failed] {$summary}");

        foreach ($event->getFailures() as $i => $failure) {
            $task = $failure->getTask();
            $text .= \sprintf("\n\n# (Failure %d/%d) %s: %s\n\n%s", $i + 1, $failureCount, $task->getType(), $task, $failure);
            $text .= $this->getTaskOutput($failure);

            if ($i < $failureCount - 1) {
                $text .= "\n\n---";
            }
        }

        $email->text($text);

        $this->mailer->send($email);
    }

    private function getTaskOutput(Result $result): string
    {
        $output = '';

        if ($result->getOutput()) {
            $output .= "\n\n## Task Output:\n\n{$result->getOutput()}";
        }

        if ($result->isException()) {
            $output .= "\n\n## Exception:\n\n{$result->getException()}";
        }

        return $output;
    }

    private function sendTaskEmail(EmailExtension $extension, Result $result): void
    {
        $email = $this->emailHeaderFor($extension);

        $this->prefixSubject($email, \sprintf('[Scheduled Task %s] %s: %s',
            $result->isFailure() ? 'Failed' : 'Succeeded',
            $result->getTask()->getType(),
            $result->getTask()
        ));

        if ($result->isFailure()) {
            $email->priority(Email::PRIORITY_HIGHEST);
        }

        $email->text($result->getDescription().$this->getTaskOutput($result));

        $this->mailer->send($email);
    }

    private function emailHeaderFor(EmailExtension $extension): Email
    {
        $email = $extension->getEmail();

        if (null !== $this->defaultTo && empty($email->getFrom())) {
            $email->from(Address::fromString($this->defaultFrom));
        }

        if (null !== $this->defaultTo && empty($email->getTo())) {
            $email->to(Address::fromString($this->defaultTo));
        }

        if (empty($email->getTo())) {
            throw new \LogicException('There is no "To" configured for the email. Either set it when adding the extension or in your configuration (config path: "zenstruck_schedule.email_handler.default_to").');
        }

        return $email;
    }

    private function prefixSubject(Email $email, string $defaultSubject): void
    {
        $subject = $email->getSubject() ?: $defaultSubject;

        $email->subject($this->subjectPrefix.$subject);
    }
}
