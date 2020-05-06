<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Extension;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\BetweenTimeHandler;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class BetweenTimeExtensionTest extends TestCase
{
    /**
     * @test
     * @dataProvider onlyBetweenExtensionSkipProvider
     */
    public function only_between_extension_skip($start, $end, $inclusive)
    {
        $start = (new \DateTime($start))->format('H:i');
        $end = (new \DateTime($end))->format('H:i');
        $task = (new MockTask())->onlyBetween($start, $end, $inclusive);

        $context = (new MockScheduleBuilder())->addTask($task)->run();

        $this->assertCount(0, $context->getRun());
        $this->assertCount(1, $skipped = $context->getSkipped());
        $this->assertSame("Only runs between {$start} and {$end}", $skipped[0]->getDescription());
    }

    public static function onlyBetweenExtensionSkipProvider()
    {
        return [
            ['+2 minutes', '+3 minutes', true],
            ['now', '+3 minutes', false],
            ['+5 minutes', '+23 hours', true],
        ];
    }

    /**
     * @test
     * @dataProvider onlyBetweenExtensionRunProvider
     */
    public function only_between_extension_run($start, $end, $inclusive)
    {
        $start = (new \DateTime($start))->format('H:i');
        $end = (new \DateTime($end))->format('H:i');
        $task = (new MockTask())->onlyBetween($start, $end, $inclusive);

        $context = (new MockScheduleBuilder())->addTask($task)->run();

        $this->assertCount(1, $context->getRun());
        $this->assertCount(0, $context->getSkipped());
    }

    public static function onlyBetweenExtensionRunProvider()
    {
        return [
            ['now', '+3 minutes', true],
            ['-1 minute', '+3 minutes', false],
            ['-1 minutes', '+23 hours', true],
        ];
    }

    /**
     * @test
     * @dataProvider unlessBetweenExtensionSkipProvider
     */
    public function unless_between_extension_skip($start, $end, $inclusive)
    {
        $start = (new \DateTime($start))->format('H:i');
        $end = (new \DateTime($end))->format('H:i');
        $task = (new MockTask())->unlessBetween($start, $end, $inclusive);

        $context = (new MockScheduleBuilder())->addTask($task)->run();

        $this->assertCount(0, $context->getRun());
        $this->assertCount(1, $skipped = $context->getSkipped());
        $this->assertSame("Only runs if not between {$start} and {$end}", $skipped[0]->getDescription());
    }

    public static function unlessBetweenExtensionSkipProvider()
    {
        return [
            ['-1 minute', '+3 minutes', false],
            ['now', '+3 minutes', true],
            ['-1 minutes', '+23 hours', true],
        ];
    }

    /**
     * @test
     * @dataProvider unlessBetweenExtensionRunProvider
     */
    public function unless_between_extension_run($start, $end, $inclusive)
    {
        $start = (new \DateTime($start))->format('H:i');
        $end = (new \DateTime($end))->format('H:i');
        $task = (new MockTask())->unlessBetween($start, $end, $inclusive);

        $context = (new MockScheduleBuilder())->addTask($task)->run();

        $this->assertCount(1, $context->getRun());
        $this->assertCount(0, $context->getSkipped());
    }

    public static function unlessBetweenExtensionRunProvider()
    {
        return [
            ['now', '+3 minutes', false],
            ['+1 minute', '+3 minutes', true],
            ['+5 minutes', '+23 hours', true],
        ];
    }
}
