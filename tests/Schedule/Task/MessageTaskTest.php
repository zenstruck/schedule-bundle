<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Messenger\Stamp\ValidationStamp;
use Zenstruck\ScheduleBundle\Schedule\Task\MessageTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MessageTaskTest extends TestCase
{
    /**
     * @test
     */
    public function object_class_is_set_as_default_description(): void
    {
        $this->assertSame(self::class, (new MessageTask($this))->getDescription());
        $this->assertSame(self::class, (new MessageTask(new Envelope($this)))->getDescription());
    }

    /**
     * @test
     */
    public function context_with_no_stamps(): void
    {
        $this->assertSame(
            [
                'Message' => self::class,
                'Stamps' => '(none)',
            ],
            (new MessageTask($this))->getContext()
        );
        $this->assertSame(
            [
                'Message' => self::class,
                'Stamps' => '(none)',
            ],
            (new MessageTask(new Envelope($this)))->getContext()
        );
    }

    /**
     * @test
     */
    public function context_with_stamps(): void
    {
        $this->assertSame(
            [
                'Message' => self::class,
                'Stamps' => 'DelayStamp, ValidationStamp',
            ],
            (new MessageTask($this, [new DelayStamp(4), new ValidationStamp([])]))->getContext()
        );
        $this->assertSame(
            [
                'Message' => self::class,
                'Stamps' => 'DelayStamp, ValidationStamp',
            ],
            (new MessageTask(new Envelope($this, [new DelayStamp(4)]), [new ValidationStamp([]), new DelayStamp(4)]))->getContext()
        );
    }
}
