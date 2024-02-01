<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Recipient\NoRecipient;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Notifier\Recipient\RecipientInterface;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\HasMissingDependencyMessage;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Pierre du Plessis <pdples@gmail.com>
 */
final class NotifierExtension implements HasMissingDependencyMessage
{
    /** @var string */
    private $hook;

    /** @var Notification */
    private $notification;

    /** @var string|null */
    private $email;

    /** @var string|null */
    private $phone;

    /**
     * @param string|string[]|null $channel
     */
    private function __construct(string $hook, $channel = null, ?string $email = null, ?string $phone = null, ?string $subject = null, ?callable $callback = null)
    {
        $this->hook = $hook;

        $notification = new Notification($subject ?? '', (array) $channel);

        if ($callback) {
            $notification = ($callback($notification) ?? $notification);
        }

        $this->notification = $notification;
        $this->email = $email;
        $this->phone = $phone;
    }

    public function __toString(): string
    {
        $channels = $this->notification->getChannels($this->getRecipient());

        if (empty($channels)) {
            return "{$this->hook}, notification output";
        }

        $channels = \implode('; ', $channels);

        return "{$this->hook}, notification output to \"{$channels}\"";
    }

    /**
     * @param string|string[]|null $channel
     */
    public static function taskAfter($channel = null, ?string $email = null, ?string $phone = null, ?string $subject = null, ?callable $callback = null): self
    {
        return new self(Task::AFTER, $channel, $email, $phone, $subject, $callback);
    }

    /**
     * @param string|string[]|null $channel
     */
    public static function taskFailure($channel = null, ?string $email = null, ?string $phone = null, ?string $subject = null, ?callable $callback = null): self
    {
        return new self(Task::FAILURE, $channel, $email, $phone, $subject, $callback);
    }

    /**
     * @param string|string[]|null $channel
     */
    public static function scheduleFailure($channel = null, ?string $email = null, ?string $phone = null, ?string $subject = null, ?callable $callback = null): self
    {
        return new self(Schedule::FAILURE, $channel, $email, $phone, $subject, $callback);
    }

    public function getNotification(): Notification
    {
        return $this->notification;
    }

    public function getRecipient(): RecipientInterface
    {
        if (empty($this->email) && empty($this->phone)) {
            return new NoRecipient();
        }

        return new Recipient($this->email ?? '', $this->phone ?? '');
    }

    public function isHook(string $expectedHook): bool
    {
        return $expectedHook === $this->hook;
    }

    public static function getMissingDependencyMessage(): string
    {
        return 'To use the notifier extension you must configure a notifier (config path: "zenstruck_schedule.notifier").';
    }
}
