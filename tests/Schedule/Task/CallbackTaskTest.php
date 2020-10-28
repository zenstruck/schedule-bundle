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
        $this->assertMatchesRegularExpression('#^\(callable\) Zenstruck\\\\ScheduleBundle\\\\Tests\\\\Schedule\\\\Task\\\\CallbackTaskTest\:\d+$#', (new CallbackTask(function() {}))->getDescription());
        $this->assertMatchesRegularExpression('#^\(callable\) Zenstruck\\\\ScheduleBundle\\\\Tests\\\\Schedule\\\\Task\\\\CallbackTaskTest\:\d+$#', (new CallbackTask([$this, __METHOD__]))->getDescription());
        $this->assertMatchesRegularExpression('#^\(callable\) Zenstruck\\\\ScheduleBundle\\\\Tests\\\\Schedule\\\\Task\\\\FixtureForCallbackTaskTest\:\d+$#', (new CallbackTask(new FixtureForCallbackTaskTest()))->getDescription());
        $this->assertMatchesRegularExpression('#^\(callable\) Zenstruck\\\\ScheduleBundle\\\\Tests\\\\Schedule\\\\Task\\\\FixtureForCallbackTaskTest\:\d+$#', (new CallbackTask([FixtureForCallbackTaskTest::class, 'staticMethod']))->getDescription());
        $this->assertSame('(callable) '.__NAMESPACE__.'\callback_task_test_function', (new CallbackTask(__NAMESPACE__.'\callback_task_test_function'))->getDescription());
    }

    /**
     * @test
     */
    public function task_has_context()
    {
        $this->assertMatchesRegularExpression('#Zenstruck\\\\ScheduleBundle\\\\Tests\\\\Schedule\\\\Task\\\\CallbackTaskTest\:\d+$#', (new CallbackTask(function() {}))->getContext()['Callable']);
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
