<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task\Runner;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\HandledStamp;
use Symfony\Component\Messenger\Stamp\SentStamp;
use Zenstruck\ScheduleBundle\Schedule\Exception\MissingDependency;
use Zenstruck\ScheduleBundle\Schedule\Task\MessageTask;
use Zenstruck\ScheduleBundle\Schedule\Task\Runner\MessageTaskRunner;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MessageTaskRunnerTest extends TestCase
{
    /**
     * @test
     */
    public function fails_if_not_handled_or_sent(): void
    {
        $bus = new MockMessageBus(new Envelope($this));

        $this->assertNull($bus->message);

        $context = self::createBuilder($bus)
            ->addTask(new MessageTask($this))
            ->run()
        ;

        $this->assertInstanceOf(self::class, $bus->message);
        $this->assertFalse($context->isSuccessful());
        $this->assertSame('Message not handled or sent to transport.', $context->getFailures()[0]->getDescription());
    }

    /**
     * @test
     */
    public function is_handled(): void
    {
        $bus = new MockMessageBus(new Envelope($this, [new HandledStamp(null, 'my handler')]));

        $this->assertNull($bus->message);

        $context = self::createBuilder($bus)
            ->addTask(new MessageTask($this))
            ->run()
        ;

        $this->assertInstanceOf(self::class, $bus->message);
        $this->assertTrue($context->isSuccessful());
        $this->assertSame('Handled by: "my handler", return: (none)', $context->getSuccessful()[0]->getOutput());
    }

    /**
     * @test
     */
    public function is_handled_by_multiple_handlers(): void
    {
        $bus = new MockMessageBus(new Envelope($this, [
            new HandledStamp(null, 'handler 1'),
            new HandledStamp(['foo'], 'handler 2'),
            new HandledStamp('bar', 'handler 3'),
            new HandledStamp(17, 'handler 3'),
        ]));

        $this->assertNull($bus->message);

        $context = self::createBuilder($bus)
            ->addTask(new MessageTask($this))
            ->run()
        ;

        $this->assertInstanceOf(self::class, $bus->message);
        $this->assertTrue($context->isSuccessful());
        $this->assertSame(
            \implode("\n", [
                'Handled by: "handler 1", return: (none)',
                'Handled by: "handler 2", return: (array)',
                'Handled by: "handler 3", return: (string) "bar"',
                'Handled by: "handler 3", return: (int) "17"',
            ]),
            $context->getSuccessful()[0]->getOutput()
        );
    }

    /**
     * @test
     */
    public function is_sent_to_transport(): void
    {
        $bus = new MockMessageBus(new Envelope($this, [new SentStamp('my transport')]));

        $this->assertNull($bus->message);

        $context = self::createBuilder($bus)
            ->addTask(new MessageTask($this))
            ->run()
        ;

        $this->assertInstanceOf(self::class, $bus->message);
        $this->assertTrue($context->isSuccessful());
        $this->assertSame('Sent to: "my transport"', $context->getSuccessful()[0]->getOutput());
    }

    /**
     * @test
     */
    public function is_sent_to_multiple_transports(): void
    {
        $bus = new MockMessageBus(new Envelope($this, [
            new SentStamp('transport 1'),
            new SentStamp('transport 2'),
        ]));

        $this->assertNull($bus->message);

        $context = self::createBuilder($bus)
            ->addTask(new MessageTask($this))
            ->run()
        ;

        $this->assertInstanceOf(self::class, $bus->message);
        $this->assertTrue($context->isSuccessful());
        $this->assertSame(
            \implode("\n", [
                'Sent to: "transport 1"',
                'Sent to: "transport 2"',
            ]),
            $context->getSuccessful()[0]->getOutput()
        );
    }

    /**
     * @test
     */
    public function is_handled_and_sent_to_transport(): void
    {
        $bus = new MockMessageBus(new Envelope($this, [
            new HandledStamp(null, 'handler'),
            new SentStamp('transport'),
        ]));

        $this->assertNull($bus->message);

        $context = self::createBuilder($bus)
            ->addTask(new MessageTask($this))
            ->run()
        ;

        $this->assertInstanceOf(self::class, $bus->message);
        $this->assertTrue($context->isSuccessful());
        $this->assertSame(
            \implode("\n", [
                'Handled by: "handler", return: (none)',
                'Sent to: "transport"',
            ]),
            $context->getSuccessful()[0]->getOutput()
        );
    }

    /**
     * @test
     */
    public function not_configured(): void
    {
        $context = (new MockScheduleBuilder())->addTask(new MessageTask($this))->run();

        $this->assertFalse($context->isSuccessful());
        $this->assertInstanceOf(MissingDependency::class, $context->getFailures()[0]->getException());
        $this->assertStringContainsString('you must install symfony/messenger', $context->getFailures()[0]->getDescription());
    }

    private static function createBuilder(MessageBusInterface $bus): MockScheduleBuilder
    {
        return (new MockScheduleBuilder())
            ->addRunner(new MessageTaskRunner($bus))
        ;
    }
}

final class MockMessageBus implements MessageBusInterface
{
    public $message;
    private $return;

    public function __construct(Envelope $return)
    {
        $this->return = $return;
    }

    public function dispatch($message, array $stamps = []): Envelope
    {
        $this->message = $message;

        return $this->return;
    }
}
