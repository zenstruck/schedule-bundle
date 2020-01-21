<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleRunContextTest extends TestCase
{
    /**
     * @test
     */
    public function cannot_access_results_if_has_not_run()
    {
        $context = new ScheduleRunContext(new Schedule());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('"The Schedule" has not yet run.');

        $context->getResults();
    }

    /**
     * @test
     */
    public function set_results_count_mismatch()
    {
        $context = new ScheduleRunContext(new Schedule(), new MockTask(), new MockTask());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The number of results (1) does not match the number of due tasks (2).');

        $context->setResults(Result::successful(new MockTask()));
    }
}
