<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Schedule\Task;

use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\StampInterface;
use Zenstruck\ScheduleBundle\Schedule\HasMissingDependencyMessage;
use Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @experimental This is experimental and may experience BC breaks
 *
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MessageTask extends Task implements HasMissingDependencyMessage
{
    /** @var object|Envelope */
    private $message;

    /** @var StampInterface[] */
    private $stamps;

    /**
     * @param object|Envelope  $message
     * @param StampInterface[] $stamps
     */
    public function __construct(object $message, array $stamps = [])
    {
        $this->message = $message;
        $this->stamps = $stamps;

        parent::__construct($this->messageClass());
    }

    /**
     * @return object|Envelope
     */
    public function getMessage(): object
    {
        return $this->message;
    }

    /**
     * @return StampInterface[]
     */
    public function getStamps(): array
    {
        return $this->stamps;
    }

    public function getContext(): array
    {
        $stamps = \array_merge(
            $this->message instanceof Envelope ? \array_keys($this->message->all()) : [],
            \array_map(static fn(StampInterface $stamp) => $stamp::class, $this->stamps)
        );
        $stamps = \array_map(
            static function(string $stamp) {
                /** @var class-string $stamp */
                return (new \ReflectionClass($stamp))->getShortName();
            },
            $stamps
        );
        $stamps = \implode(', ', \array_unique($stamps));

        return [
            'Message' => $this->messageClass(),
            'Stamps' => $stamps ?: '(none)',
        ];
    }

    public static function getMissingDependencyMessage(): string
    {
        return 'To use the message task you must install symfony/messenger (composer require symfony/messenger) and enable (config path: "zenstruck_schedule.messenger").';
    }

    private function messageClass(): string
    {
        return $this->message instanceof Envelope ? \get_class($this->message->getMessage()) : \get_class($this->message);
    }
}
