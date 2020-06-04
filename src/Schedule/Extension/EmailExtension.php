<?php

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
    private $hook;
    private $email;

    /**
     * @param string|Address|string[]|Address[]|null $to
     */
    private function __construct(string $hook, $to = null, string $subject = null, callable $callback = null)
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

        $to = \array_map(function(Address $address) { return $address->toString(); }, $to);
        $to = \implode('; ', $to);

        return "{$this->hook}, email output to \"{$to}\"";
    }

    public static function taskAfter($to = null, string $subject = null, callable $callback = null): self
    {
        return new self(Task::AFTER, $to, $subject, $callback);
    }

    public static function taskFailure($to = null, string $subject = null, callable $callback = null): self
    {
        return new self(Task::FAILURE, $to, $subject, $callback);
    }

    public static function scheduleFailure($to = null, string $subject = null, callable $callback = null): self
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
