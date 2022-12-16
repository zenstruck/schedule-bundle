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

use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\HasMissingDependencyMessage;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EmailExtension implements HasMissingDependencyMessage
{
    /** @var string */
    private $hook;

    /** @var Email */
    private $email;

    /**
     * @param string|Address|string[]|Address[]|null $to
     */
    private function __construct(string $hook, $to = null, ?string $subject = null, ?callable $callback = null)
    {
        $this->hook = $hook;

        $email = new Email();

        if (null !== $to) {
            $email->to(...(array) $to);
        }

        if ($subject) {
            $email->subject($subject);
        }

        if ($callback) {
            $callback($email);
        }

        $this->email = $email;
    }

    public function __toString(): string
    {
        $to = $this->email->getTo();

        if (empty($to)) {
            return "{$this->hook}, email output";
        }

        $to = \array_map(fn(Address $address) => $address->toString(), $to);
        $to = \implode('; ', $to);

        return "{$this->hook}, email output to \"{$to}\"";
    }

    /**
     * @param string|Address|string[]|Address[]|null $to
     */
    public static function taskAfter($to = null, ?string $subject = null, ?callable $callback = null): self
    {
        return new self(Task::AFTER, $to, $subject, $callback);
    }

    /**
     * @param string|Address|string[]|Address[]|null $to
     */
    public static function taskFailure($to = null, ?string $subject = null, ?callable $callback = null): self
    {
        return new self(Task::FAILURE, $to, $subject, $callback);
    }

    /**
     * @param string|Address|string[]|Address[]|null $to
     */
    public static function scheduleFailure($to = null, ?string $subject = null, ?callable $callback = null): self
    {
        return new self(Schedule::FAILURE, $to, $subject, $callback);
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function isHook(string $expectedHook): bool
    {
        return $expectedHook === $this->hook;
    }

    public static function getMissingDependencyMessage(): string
    {
        return 'To use the email extension you must configure a mailer (config path: "zenstruck_schedule.mailer").';
    }
}
