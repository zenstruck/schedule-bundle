<?php

namespace Zenstruck\ScheduleBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Psr\Log\Test\TestLogger;
use Zenstruck\ScheduleBundle\EventListener\ScheduleLoggerSubscriber;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\CallbackExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\Handler\CallbackHandler;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduleLoggerSubscriberTest extends TestCase
{
    private $logger;

    protected function setUp(): void
    {
        parent::setUp();

        $this->logger = new TestLogger();
    }

    /**
     * @test
     */
    public function no_scheduled_tasks()
    {
        $this->createRunnerBuilder()->run();

        $this->assertCount(1, $this->logger->records);
        $this->assertTrue($this->logger->hasDebugThatContains('No tasks due to run.'));
    }

    /**
     * @test
     */
    public function run_successful_task()
    {
        $this->createRunnerBuilder()
            ->addTask(MockTask::success('my task'))
            ->run()
        ;
        $this->assertCount(4, $this->logger->records);
        $this->assertTrue($this->logger->hasInfoThatContains('Running 1 due task.'));
        $this->assertTrue($this->logger->hasInfoThatContains('Running "MockTask: my task"'));
        $this->assertTrue($this->logger->hasInfoThatContains('Successfully ran "MockTask: my task"'));
        $this->assertTrue($this->logger->hasInfoThatContains('1/1 tasks ran'));
    }

    /**
     * @test
     */
    public function run_task_that_throws_exception()
    {
        $this->createRunnerBuilder()
            ->addTask(MockTask::exception(new \Exception('failed...'), 'my task'))
            ->run()
        ;

        $this->assertCount(4, $this->logger->records);
        $this->assertTrue($this->logger->hasInfoThatContains('Running 1 due task.'));
        $this->assertTrue($this->logger->hasInfoThatContains('Running "MockTask: my task"'));
        $this->assertTrue($this->logger->hasCriticalThatContains('Exception thrown when running "MockTask: my task"'));
        $this->assertTrue($this->logger->hasErrorThatContains('1/1 tasks ran'));
        $this->assertSame('failed...', $this->logger->records[2]['context']['exception']->getMessage());
    }

    /**
     * @test
     */
    public function run_task_that_fails()
    {
        $this->createRunnerBuilder()
            ->addTask(MockTask::failure('failed', 'my task', 'task output'))
            ->run()
        ;

        $this->assertCount(4, $this->logger->records);
        $this->assertTrue($this->logger->hasInfoThatContains('Running 1 due task.'));
        $this->assertTrue($this->logger->hasInfoThatContains('Running "MockTask: my task"'));
        $this->assertTrue($this->logger->hasErrorThatContains('Failure when running "MockTask: my task"'));
        $this->assertTrue($this->logger->hasErrorThatContains('1/1 tasks ran'));
        $this->assertSame('task output', $this->logger->records[2]['context']['output']);
    }

    /**
     * @test
     */
    public function run_task_that_skips()
    {
        $this->createRunnerBuilder()
            ->addTask(MockTask::success()->skip('skip reason', true))
            ->addHandler(new CallbackHandler())
            ->run()
        ;

        $this->assertCount(4, $this->logger->records);
        $this->assertTrue($this->logger->hasInfoThatContains('Running 1 due task.'));
        $this->assertTrue($this->logger->hasInfoThatContains('Running "MockTask: my task"'));
        $this->assertTrue($this->logger->hasInfoThatContains('Skipped "MockTask: my task" (skip reason)'));
        $this->assertTrue($this->logger->hasInfoThatContains('0/1 tasks ran'));
    }

    /**
     * @test
     */
    public function run_schedule_that_skips()
    {
        $context = $this->createRunnerBuilder()
            ->addTask(new MockTask())
            ->addHandler(new CallbackHandler())
            ->addExtension(CallbackExtension::scheduleFilter(function () {
                throw new SkipSchedule('the schedule has skipped');
            }))
            ->run()
        ;

        $this->assertTrue($context->isSkipped());
        $this->assertTrue($context->isSuccessful());
        $this->assertCount(2, $this->logger->records);
        $this->assertTrue($this->logger->hasInfoThatContains('Running 1 due task.'));
        $this->assertTrue($this->logger->hasInfoThatContains('the schedule has skipped'));
    }

    /**
     * @test
     */
    public function force_run_tasks()
    {
        $this->createRunnerBuilder()
            ->addTask($task = MockTask::success('my task'))
            ->run($task->getId())
        ;
        $this->assertCount(4, $this->logger->records);
        $this->assertTrue($this->logger->hasInfoThatContains('Force running 1 task.'));
        $this->assertTrue($this->logger->hasInfoThatContains('Force running "MockTask: my task"'));
        $this->assertTrue($this->logger->hasInfoThatContains('Successfully ran "MockTask: my task"'));
        $this->assertTrue($this->logger->hasInfoThatContains('1/1 tasks ran'));
    }

    private function createRunnerBuilder(): MockScheduleBuilder
    {
        return (new MockScheduleBuilder())
            ->addSubscriber(new ScheduleLoggerSubscriber($this->logger))
        ;
    }
}
