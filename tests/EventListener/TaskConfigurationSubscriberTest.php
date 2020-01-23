<?php

namespace Zenstruck\ScheduleBundle\Tests\EventListener;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Zenstruck\ScheduleBundle\DependencyInjection\Configuration;
use Zenstruck\ScheduleBundle\EventListener\TaskConfigurationSubscriber;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;
use Zenstruck\ScheduleBundle\Schedule\Task\NullTask;
use Zenstruck\ScheduleBundle\Schedule\Task\ProcessTask;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockScheduleBuilder;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TaskConfigurationSubscriberTest extends TestCase
{
    /**
     * @test
     */
    public function minimal_task_configuration()
    {
        $schedule = $this->createSchedule([
            [
                'task' => 'my:command',
                'frequency' => '0 * * * *',
            ],
            [
                'task' => 'another:command',
                'frequency' => '@yearly',
            ],
        ]);

        $this->assertCount(2, $schedule->all());

        [$task1, $task2] = $schedule->all();

        $this->assertInstanceOf(CommandTask::class, $task1);
        $this->assertSame('my:command', $task1->getDescription());
        $this->assertNull($task1->getTimezone());
        $this->assertSame('0 * * * *', (string) $task1->getExpression());
        $this->assertCount(0, $task1->getExtensions());

        $this->assertInstanceOf(CommandTask::class, $task2);
        $this->assertSame('another:command', $task2->getDescription());
        $this->assertNull($task1->getTimezone());
        $this->assertSame('@yearly', (string) $task2->getExpression());
        $this->assertCount(0, $task2->getExtensions());
    }

    /**
     * @test
     */
    public function can_configure_process_tasks()
    {
        $schedule = $this->createSchedule([
            [
                'task' => 'bash: /bin/script',
                'frequency' => '0 * * * *',
            ],
        ]);

        $this->assertCount(1, $schedule->all());
        $this->assertInstanceOf(ProcessTask::class, $schedule->all()[0]);
        $this->assertSame('/bin/script', $schedule->all()[0]->getDescription());
        $this->assertSame('0 * * * *', (string) $schedule->all()[0]->getExpression());
        $this->assertCount(0, $schedule->all()[0]->getExtensions());
    }

    /**
     * @test
     */
    public function can_configure_compound_task()
    {
        $schedule = $this->createSchedule([
            [
                'task' => [
                    'my:command arg --option=foo',
                    'bash:/my-script',
                ],
                'frequency' => '0 * * * *',
                'without_overlapping' => null,
            ],
        ]);

        $this->assertCount(2, $schedule->all());

        [$task1, $task2] = $schedule->all();

        $this->assertInstanceOf(CommandTask::class, $task1);
        $this->assertSame('my:command', $task1->getDescription());
        $this->assertSame('0 * * * *', (string) $task1->getExpression());
        $this->assertCount(1, $task1->getExtensions());
        $this->assertSame('Without overlapping', (string) $task1->getExtensions()[0]);

        $this->assertInstanceOf(ProcessTask::class, $task2);
        $this->assertSame('/my-script', $task2->getDescription());
        $this->assertSame('0 * * * *', (string) $task2->getExpression());
        $this->assertCount(1, $task2->getExtensions());
        $this->assertSame('Without overlapping', (string) $task2->getExtensions()[0]);
    }

    /**
     * @test
     */
    public function can_configure_compound_task_with_descriptions()
    {
        $schedule = $this->createSchedule([
            [
                'task' => [
                    'my command' => 'my:command arg --option=foo',
                    'another command' => 'bash:/my-script',
                ],
                'frequency' => '0 * * * *',
                'without_overlapping' => null,
            ],
        ]);

        $this->assertCount(2, $schedule->all());

        [$task1, $task2] = $schedule->all();

        $this->assertInstanceOf(CommandTask::class, $task1);
        $this->assertSame('my command', $task1->getDescription());
        $this->assertSame('0 * * * *', (string) $task1->getExpression());
        $this->assertCount(1, $task1->getExtensions());
        $this->assertSame('Without overlapping', (string) $task1->getExtensions()[0]);

        $this->assertInstanceOf(ProcessTask::class, $task2);
        $this->assertSame('another command', $task2->getDescription());
        $this->assertSame('0 * * * *', (string) $task2->getExpression());
        $this->assertCount(1, $task2->getExtensions());
        $this->assertSame('Without overlapping', (string) $task2->getExtensions()[0]);
    }

    /**
     * @test
     */
    public function can_configure_null_task()
    {
        $schedule = $this->createSchedule([
            [
                'task' => null,
                'frequency' => '0 * * * *',
                'description' => 'my task',
            ],
        ]);

        $this->assertCount(1, $schedule->all());
        $this->assertInstanceOf(NullTask::class, $schedule->all()[0]);
        $this->assertSame('my task', $schedule->all()[0]->getDescription());
        $this->assertSame('0 * * * *', (string) $schedule->all()[0]->getExpression());
    }

    /**
     * @test
     */
    public function can_configure_hashed_frequency_expression()
    {
        $schedule = $this->createSchedule([
            [
                'task' => 'my:command1',
                'frequency' => '# * * * *',
            ],
            [
                'task' => 'my:command1',
                'frequency' => '# * * * *',
                'description' => 'my description',
            ],
            [
                'task' => 'my:command2',
                'frequency' => '# #(9-17) * * *',
            ],
            [
                'task' => 'my:command3',
                'frequency' => '#daily',
            ],
            [
                'task' => 'my:command4',
                'frequency' => '#midnight',
            ],
        ]);

        $this->assertCount(5, $schedule->all());

        [$task1, $task2, $task3, $task4, $task5] = $schedule->all();

        $this->assertSame('16 * * * *', (string) $task1->getExpression());
        $this->assertSame('# * * * *', $task1->getExpression()->getRawValue());

        $this->assertSame('10 * * * *', (string) $task2->getExpression(), 'Different description changes minute');
        $this->assertSame('# * * * *', $task2->getExpression()->getRawValue());

        $this->assertSame('9 12 * * *', (string) $task3->getExpression());
        $this->assertSame('# #(9-17) * * *', $task3->getExpression()->getRawValue());

        $this->assertSame('29 17 * * *', (string) $task4->getExpression());
        $this->assertSame('#daily', $task4->getExpression()->getRawValue());

        $this->assertSame('11 2 * * *', (string) $task5->getExpression());
        $this->assertSame('#midnight', $task5->getExpression()->getRawValue());
    }

    /**
     * @test
     */
    public function full_task_configuration()
    {
        $schedule = $this->createSchedule([
            [
                'task' => 'my:command --option',
                'frequency' => '0 0 * * *',
                'description' => 'my description',
                'timezone' => 'UTC',
                'without_overlapping' => null,
                'only_between' => [
                    'start' => 9,
                    'end' => 17,
                ],
                'unless_between' => [
                    'start' => 12,
                    'end' => '13:30',
                ],
                'ping_before' => [
                    'url' => 'https://example.com/before',
                ],
                'ping_after' => [
                    'url' => 'https://example.com/after',
                ],
                'ping_on_success' => [
                    'url' => 'https://example.com/success',
                ],
                'ping_on_failure' => [
                    'url' => 'https://example.com/failure',
                    'method' => 'POST',
                ],
                'email_after' => null,
                'email_on_failure' => [
                    'to' => 'sales@example.com',
                    'subject' => 'my subject',
                ],
            ],
        ]);

        $task = $schedule->all()[0];
        $extensions = $task->getExtensions();

        $this->assertSame('my description', $task->getDescription());
        $this->assertSame('UTC', $task->getTimezone()->getName());
        $this->assertCount(9, $extensions);
        $this->assertSame('Without overlapping', (string) $extensions[0]);
        $this->assertSame('Only run between 9:00 and 17:00', (string) $extensions[1]);
        $this->assertSame('Only run if not between 12:00 and 13:30', (string) $extensions[2]);
        $this->assertSame('Before Task, ping "https://example.com/before"', (string) $extensions[3]);
        $this->assertSame('After Task, ping "https://example.com/after"', (string) $extensions[4]);
        $this->assertSame('On Task Success, ping "https://example.com/success"', (string) $extensions[5]);
        $this->assertSame('On Task Failure, ping "https://example.com/failure"', (string) $extensions[6]);
        $this->assertSame('After Task, email output', (string) $extensions[7]);
        $this->assertSame('On Task Failure, email output to "sales@example.com"', (string) $extensions[8]);
    }

    /**
     * @test
     */
    public function can_configure_task_services()
    {
        $serviceTask = new MockTask();
        $schedule = $this->createSchedule([
            [
                'task' => '@my_task1',
                'frequency' => '0 0 * * *',
                'description' => 'task1',
            ],
            [
                'task' => [
                    'task2' => '@my_task1',
                    'task3' => 'my:command',
                    'task4' => '@my_task2',
                ],
                'frequency' => '0 0 * * *',
            ],
        ], new ServiceLocator([
            'my_task1' => function () use ($serviceTask) {
                return $serviceTask;
            },
            'my_task2' => function () use ($serviceTask) {
                return $serviceTask;
            },
        ]));

        $this->assertCount(4, $schedule->all());
        $this->assertSame('MockTask: task1', (string) $schedule->all()[0]);
        $this->assertSame('MockTask: task2', (string) $schedule->all()[1]);
        $this->assertSame('CommandTask: task3', (string) $schedule->all()[2]);
        $this->assertSame('MockTask: task4', (string) $schedule->all()[3]);
    }

    private function createSchedule(array $taskConfig, ContainerInterface $taskLocator = null): Schedule
    {
        $processor = new Processor();
        $config = $processor->processConfiguration(new Configuration(), [['tasks' => $taskConfig]]);

        return (new MockScheduleBuilder())
            ->addSubscriber(new TaskConfigurationSubscriber(
                $config['tasks'],
                $taskLocator ?: new ServiceLocator([])
            ))
            ->getRunner()
            ->buildSchedule()
        ;
    }
}
