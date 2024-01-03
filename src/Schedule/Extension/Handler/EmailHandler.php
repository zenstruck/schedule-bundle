<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
    use Schedule\TaskOutput;

    /** @var MailerInterface */
    private $mailer;

    /** @var string|null */
    private $defaultFrom;

    /** @var string|null */
    private $defaultTo;

    /** @var string|null */
    private $subjectPrefix;

    public function __construct(MailerInterface $mailer, ?string $defaultFrom = null, ?string $defaultTo = null, ?string $subjectPrefix = null)
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

    private function sendTaskEmail(EmailExtension $extension, Result $result, ScheduleRunContext $context): void
    {
        $email = $this->emailHeaderFor($extension);

        $this->prefixSubject($email, \sprintf('[Scheduled Task %s] %s',
            $result->isFailure() ? 'Failed' : 'Succeeded',
            $result->getTask(),
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
            $email->from(Address::create($this->defaultFrom));
        }

        if (null !== $this->defaultTo && empty($email->getTo())) {
            $email->to(Address::create($this->defaultTo));
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
