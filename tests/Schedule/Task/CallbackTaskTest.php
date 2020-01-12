<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Task;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Task\CallbackTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CallbackTaskTest extends TestCase
{
    /**
     * @test
     */
    public function has_default_description()
    {
        $this->assertRegExp('#^\(callable\) Zenstruck\\\\ScheduleBundle\\\\Tests\\\\Schedule\\\\Task\\\\CallbackTaskTest\:\d+$#', (new CallbackTask(function () {}))->getDescription());
        $this->assertRegExp('#^\(callable\) Zenstruck\\\\ScheduleBundle\\\\Tests\\\\Schedule\\\\Task\\\\CallbackTaskTest\:\d+$#', (new CallbackTask([$this, __METHOD__]))->getDescription());
        $this->assertRegExp('#^\(callable\) Zenstruck\\\\ScheduleBundle\\\\Tests\\\\Schedule\\\\Task\\\\FixtureForCallbackTaskTest\:\d+$#', (new CallbackTask(new FixtureForCallbackTaskTest()))->getDescription());
        $this->assertRegExp('#^\(callable\) Zenstruck\\\\ScheduleBundle\\\\Tests\\\\Schedule\\\\Task\\\\FixtureForCallbackTaskTest\:\d+$#', (new CallbackTask([FixtureForCallbackTaskTest::class, 'staticMethod']))->getDescription());
        $this->assertSame('(callable) '.__NAMESPACE__.'\callback_task_test_function', (new CallbackTask(__NAMESPACE__.'\callback_task_test_function'))->getDescription());
    }

    /**
     * @test
     */
    public function can_create_successful_result()
    {
        $result = (new CallbackTask(function () {}))();

        $this->assertTrue($result->isSuccessful());
        $this->assertNull($result->getOutput());
    }

    /**
     * @test
     * @dataProvider outputProvider
     */
    public function stringifies_output($output, $expectedOutput)
    {
        $result = (new CallbackTask(function () use ($output) { return $output; }))();

        $this->assertTrue($result->isSuccessful());
        $this->assertSame($expectedOutput, $result->getOutput());
    }

    public static function outputProvider()
    {
        $stringClass = new class() {
            public function __toString(): string
            {
                return 'as string';
            }
        };

        return [
            [null, null],
            [10, '10'],
            [true, '1'],
            [false, ''],
            [new \stdClass(), '[object] stdClass'],
            [['foo'], '(array)'],
            [$stringClass, 'as string'],
        ];
    }
}

class FixtureForCallbackTaskTest
{
    public function __invoke()
    {
    }

    public static function staticMethod()
    {
    }
}

function callback_task_test_function()
{
}
