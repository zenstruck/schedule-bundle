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

use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Notifier\Recipient\NoRecipient;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Notifier\Recipient\RecipientInterface;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\Extension\NotifierExtension;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Schedule\Task\TaskRunContext;
use Zenstruck\ScheduleBundle\Schedule\TaskOutput;

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 */
final class NotifierHandler extends ExtensionHandler
{
    use TaskOutput;

    /** @var NotifierInterface */
    private $notifier;

    /** @var string */
    private $defaultEmail;

    /** @var string */
    private $defaultPhone;

    /** @var string|null */
    private $subjectPrefix;

    /** @var array<int, string> */
    private $defaultChannel;

    /**
     * @param string|string[] $defaultChannel
     */
    public function __construct(NotifierInterface $notifier, $defaultChannel = null, ?string $defaultEmail = null, ?string $defaultPhone = null, ?string $subjectPrefix = null)
    {
        $this->notifier = $notifier;
        $this->defaultEmail = $defaultEmail ?? '';
        $this->defaultPhone = $defaultPhone ?? '';
        $this->subjectPrefix = $subjectPrefix;
        $this->defaultChannel = (array) $defaultChannel;
    }

    /**
     * @param NotifierExtension $extension
     */
    public function onScheduleFailure(ScheduleRunContext $context, object $extension): void
    {
        if ($extension->isHook(Schedule::FAILURE)) {
            $this->sendScheduleNotification($context, $extension);
        }
    }

    /**
     * @param NotifierExtension $extension
     */
    public function afterTask(TaskRunContext $context, object $extension): void
    {
        if ($extension->isHook(Task::AFTER)) {
            $this->sendTaskNotification($extension, $context->getResult(), $context->getScheduleRunContext());
        }
    }

    /**
     * @param NotifierExtension $extension
     */
    public function onTaskFailure(TaskRunContext $context, object $extension): void
    {
        if ($extension->isHook(Task::FAILURE)) {
            $this->sendTaskNotification($extension, $context->getResult(), $context->getScheduleRunContext());
        }
    }

    public function supports(object $extension): bool
    {
        return $extension instanceof NotifierExtension;
    }

    private function sendScheduleNotification(ScheduleRunContext $context, NotifierExtension $extension): void
    {
        $notification = $extension->getNotification();
        $failureCount = \count($context->getFailures());
        $summary = \sprintf('%d task%s failed', $failureCount, $failureCount > 1 ? 's' : '');
        $text = $summary;

        $notification->importance(Notification::IMPORTANCE_HIGH);
        $this->prefixSubject($notification, "[Schedule Failure] {$summary}");

        foreach ($context->getFailures() as $i => $failure) {
            $task = $failure->getTask();
            $text .= \sprintf("\n\n# (Failure %d/%d) %s\n\n", $i + 1, $failureCount, $task);
            $text .= $this->getTaskOutput($failure, $context, false);

            if ($i < $failureCount - 1) {
                $text .= "\n\n---";
            }
        }

        $notification->content($text);

        if ([] === $notification->getChannels($this->getRecipient($extension))) {
            $notification->channels($this->defaultChannel);
        }

        $this->notifier->send($notification, $this->getRecipient($extension));
    }

    private function sendTaskNotification(NotifierExtension $extension, Result $result, ScheduleRunContext $context): void
    {
        $notification = $extension->getNotification();

        $this->prefixSubject($notification, \sprintf('[Scheduled Task %s] %s',
            $result->isFailure() ? 'Failed' : 'Succeeded',
            $result->getTask()
        ));

        if ($result->isFailure()) {
            $notification->importance(Notification::IMPORTANCE_HIGH);
        }

        $notification->content($this->getTaskOutput($result, $context, false));

        if ([] === $notification->getChannels($this->getRecipient($extension))) {
            if (empty($this->defaultChannel)) {
                throw new \LogicException('There is no "Channel" configured for the notification. Either set it when adding the extension or in your configuration (config path: "zenstruck_schedule.notifier.default_channel").');
            }

            $notification->channels($this->defaultChannel);
        }

        $this->notifier->send($notification, $this->getRecipient($extension));
    }

    private function prefixSubject(Notification $notification, string $defaultSubject): void
    {
        $subject = $notification->getSubject() ?: $defaultSubject;

        $notification->subject($this->subjectPrefix.$subject);
    }

    private function getRecipient(NotifierExtension $extension): RecipientInterface
    {
        $recipient = $extension->getRecipient();

        if ($recipient instanceof NoRecipient && ('' !== $this->defaultEmail || '' !== $this->defaultPhone)) {
            $recipient = new Recipient($this->defaultEmail, $this->defaultPhone);
        }

        return $recipient;
    }
}
