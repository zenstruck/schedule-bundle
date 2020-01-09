<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Zenstruck\ScheduleBundle\Schedule\Extension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EmailExtension implements Extension, HasMissingHandlerMessage
{
    private $hook;
    private $email;

    /**
     * @param string|Address|string[]|Address[]|null $to
     */
    private function __construct(string $hook, $to = null, string $subject = null, callable $callback = null)
    {
        if (!\interface_exists(MailerInterface::class)) {
            throw new \LogicException(\sprintf('Symfony Mailer is required to use the "%s" extension. Install with "composer require symfony/mailer".', self::class));
        }

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

        $to = \array_map(function (Address $address) { return $address->toString(); }, $to);
        $to = \implode('; ', $to);

        return "{$this->hook}, email output to \"{$to}\"";
    }

    public static function taskAfter($to = null, string $subject = null, callable $callback = null): self
    {
        return new self(Extension::TASK_AFTER, $to, $subject, $callback);
    }

    public static function taskFailure($to = null, string $subject = null, callable $callback = null): self
    {
        return new self(Extension::TASK_FAILURE, $to, $subject, $callback);
    }

    public static function scheduleFailure($to = null, string $subject = null, callable $callback = null): self
    {
        return new self(Extension::SCHEDULE_FAILURE, $to, $subject, $callback);
    }

    public function getEmail(): Email
    {
        return $this->email;
    }

    public function isHook(string $expectedHook): bool
    {
        return $expectedHook === $this->hook;
    }

    public function getMissingHandlerMessage(): string
    {
        return 'To use the email extension you must configure a mailer (config path: "zenstruck_schedule.email_handler").';
    }
}
