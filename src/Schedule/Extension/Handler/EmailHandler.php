<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\MissingDependency;
use Zenstruck\ScheduleBundle\Schedule\Extension\EmailExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;

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
    public function onScheduleFailure(ScheduleRunContext $context, object $extension): void
    {
        if ($extension->isHook(Schedule::FAILURE)) {
            $this->sendScheduleEmail($context, $extension);
        }
    }

    /**
     * @param EmailExtension $extension
     */
    public function afterTask(TaskRunContext $context, object $extension): void
    {
        if ($extension->isHook(Task::AFTER)) {
            $this->sendTaskEmail($extension, $context->getResult(), $context->getScheduleRunContext());
        }
    }

    /**
     * @param EmailExtension $extension
     */
    public function onTaskFailure(TaskRunContext $context, object $extension): void
    {
        if ($extension->isHook(Task::FAILURE)) {
            $this->sendTaskEmail($extension, $context->getResult(), $context->getScheduleRunContext());
        }
    }

    public function supports(object $extension): bool
    {
        return $extension instanceof EmailExtension;
    }

    private function sendScheduleEmail(ScheduleRunContext $context, EmailExtension $extension): void
    {
        $email = $this->emailHeaderFor($extension);
        $failureCount = \count($context->getFailures());
        $summary = \sprintf('%d task%s failed', $failureCount, $failureCount > 1 ? 's' : '');
        $text = $summary;

        $email->priority(Email::PRIORITY_HIGHEST);
        $this->prefixSubject($email, "[Schedule Failure] {$summary}");

        foreach ($context->getFailures() as $i => $failure) {
            $task = $failure->getTask();
            $text .= \sprintf("\n\n# (Failure %d/%d) %s\n\n", $i + 1, $failureCount, $task);
            $text .= $this->getTaskOutput($failure, $context);

            if ($i < $failureCount - 1) {
                $text .= "\n\n---";
            }
        }

        $email->text($text);

        $this->mailer->send($email);
    }

    private function getTaskOutput(Result $result, ScheduleRunContext $context): string
    {
        $output = '';

        if ($context->isForceRun()) {
            $output = "!! This task was force run !!\n\n";
        }

        $output .= \sprintf("Result: \"%s\"\n\nTask ID: %s", $result, $result->getTask()->getId());

        if ($result->getOutput()) {
            $output .= "\n\n## Task Output:\n\n{$result->getOutput()}";
        }

        if ($result->isException()) {
            $output .= "\n\n## Exception:\n\n{$result->getException()}";
        }

        return $output;
    }

    private function sendTaskEmail(EmailExtension $extension, Result $result, ScheduleRunContext $context): void
    {
        $email = $this->emailHeaderFor($extension);

        $this->prefixSubject($email, \sprintf('[Scheduled Task %s] %s',
            $result->isFailure() ? 'Failed' : 'Succeeded',
            $result->getTask()
        ));

        if ($result->isFailure()) {
            $email->priority(Email::PRIORITY_HIGHEST);
        }

        $email->text($this->getTaskOutput($result, $context));

        $this->mailer->send($email);
    }

    private function emailHeaderFor(EmailExtension $extension): Email
    {
        $email = $extension->getEmail();

        if (null !== $this->defaultFrom && empty($email->getFrom())) {
            $email->from(Address::fromString($this->defaultFrom));
        }

        if (null !== $this->defaultTo && empty($email->getTo())) {
            $email->to(Address::fromString($this->defaultTo));
        }

        if (empty($email->getTo())) {
            throw new MissingDependency('There is no "To" configured for the email. Either set it when adding the extension or in your configuration (config path: "zenstruck_schedule.mailer.default_to").');
        }

        return $email;
    }

    private function prefixSubject(Email $email, string $defaultSubject): void
    {
        $subject = $email->getSubject() ?: $defaultSubject;

        $email->subject($this->subjectPrefix.$subject);
    }
}
