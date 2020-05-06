<?php

namespace Zenstruck\ScheduleBundle\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;
use Symfony\Component\Process\Process;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\CallbackTask;
use Zenstruck\ScheduleBundle\Schedule\Task\CommandTask;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
class ScheduleTest extends TestCase
{
    /**
     * @test
     */
    public function can_add_tasks()
    {
        $schedule = new Schedule();

        $schedule->add(new CallbackTask(function () {}))->description('task1');
        $schedule->addCallback(function () {})->description('task2');
        $schedule->addProcess('php -v')->description('task3');
        $schedule->addProcess(new Process(['php -v']))->description('task4');
        $schedule->addCommand('my:command')->description('task5');

        $this->assertCount(5, $schedule->all());
        $this->assertSame(['task1', 'task2', 'task3', 'task4', 'task5'], \array_map(
            function (Task $task) {
                return $task->getDescription();
            },
            $schedule->all()
        ));

        $this->assertCount(5, $schedule->all(), 'Caches the tasks');

        $schedule->addCommand('another:command');

        $this->assertCount(6, $schedule->all(), 'Resets the task cache on add');
    }

    /**
     * @test
     */
    public function can_add_compound_tasks()
    {
        $schedule = new Schedule();

        $schedule->addCommand('my:command')->description('task1')->tuesdays();
        $schedule->addCompound()
            ->addCommand('another:command', [], 'task2')
            ->addCallback(function () {}, 'task3')
            ->addProcess('php -v', 'task4')
            ->addProcess(new Process(['php -v']), 'task5')
            ->add((new CommandTask('yet:another:command'))
                ->description('task6')
                ->sundays()
                ->timezone('America/Los_Angeles')
            )
            ->timezone('UTC')
            ->mondays()
            ->onSingleServer()
        ;

        $this->assertCount(6, $schedule->all());
        $this->assertSame('task1', $schedule->all()[0]->getDescription());
        $this->assertSame('* * * * 2', (string) $schedule->all()[0]->getExpression());
        $this->assertNull($schedule->all()[0]->getTimezone());
        $this->assertCount(0, $schedule->all()[0]->getExtensions());
        $this->assertSame('task2', $schedule->all()[1]->getDescription());
        $this->assertSame('* * * * 1', (string) $schedule->all()[1]->getExpression());
        $this->assertSame('UTC', $schedule->all()[1]->getTimezone()->getName());
        $this->assertCount(1, $schedule->all()[1]->getExtensions());
        $this->assertSame('task3', $schedule->all()[2]->getDescription());
        $this->assertSame('* * * * 1', (string) $schedule->all()[2]->getExpression());
        $this->assertSame('UTC', $schedule->all()[2]->getTimezone()->getName());
        $this->assertCount(1, $schedule->all()[2]->getExtensions());
        $this->assertSame('task4', $schedule->all()[3]->getDescription());
        $this->assertSame('* * * * 1', (string) $schedule->all()[3]->getExpression());
        $this->assertSame('UTC', $schedule->all()[3]->getTimezone()->getName());
        $this->assertCount(1, $schedule->all()[3]->getExtensions());
        $this->assertSame('task5', $schedule->all()[4]->getDescription());
        $this->assertSame('* * * * 1', (string) $schedule->all()[4]->getExpression());
        $this->assertSame('UTC', $schedule->all()[4]->getTimezone()->getName());
        $this->assertCount(1, $schedule->all()[4]->getExtensions());
        $this->assertSame('task6', $schedule->all()[5]->getDescription());
        $this->assertSame('* * * * 1', (string) $schedule->all()[5]->getExpression());
        $this->assertSame('UTC', $schedule->all()[5]->getTimezone()->getName());
        $this->assertCount(1, $schedule->all()[5]->getExtensions());
    }

    /**
     * @test
     */
    public function can_get_due_tasks()
    {
        $schedule = new Schedule();

        $schedule->addCallback(function () {})->description('task1');
        $notDueTask = $schedule->addProcess('php -v')->description('task2')->sundays();

        if ('Sun' === \date('D')) {
            $notDueTask->mondays();
        }

        $this->assertCount(2, $schedule->all());
        $this->assertCount(1, $schedule->due(new \DateTime()));
        $this->assertCount(1, $schedule->due(new \DateTime()), 'Due tasks are cached');
        $this->assertSame('task1', $schedule->due(new \DateTime())[0]->getDescription());

        $schedule->addCommand('my:command')->description('task3');

        $this->assertCount(2, $schedule->due(new \DateTime()), 'Resets the due task cache');
    }

    /**
     * @test
     */
    public function due_tasks_are_ordered_by_when_they_are_defined()
    {
        $schedule = new Schedule();

        $schedule->addCommand('my:command')->description('task1');
        $schedule->addCompound()
            ->addCommand('another:command', [], 'task2')
            ->addCallback(function () {}, 'task3')
            ->addProcess('php -v', 'task4')
            ->addProcess(new Process(['php -v']), 'task5')
            ->onSingleServer()
        ;
        $schedule->addCommand('my:command')->description('task6');

        $this->assertSame(
            [
                'task1',
                'task2',
                'task3',
                'task4',
                'task5',
                'task6',
            ], \array_map(function (Task $task) {
                return $task->getDescription();
            }, $schedule->due(new \DateTime()))
        );
    }

    /**
     * @test
     */
    public function has_unique_id_based_on_tasks()
    {
        $schedule1 = new Schedule();
        $schedule1->addCommand('my:command');
        $schedule2 = new Schedule();
        $schedule2->addCommand('my:command');
        $schedule3 = new Schedule();
        $schedule3->addCommand('another:command');
        $schedule4 = new Schedule();
        $schedule4->addCommand('my:command');
        $schedule4->addCommand('another:command');

        $this->assertSame((new Schedule())->getId(), (new Schedule())->getId());
        $this->assertSame($schedule1->getId(), $schedule2->getId());
        $this->assertNotSame($schedule2->getId(), $schedule3->getId());
        $this->assertNotSame($schedule2->getId(), $schedule4->getId());
    }

    /**
     * @test
     */
    public function can_add_single_server_extension()
    {
        $schedule = new Schedule();
        $schedule->addCommand('my:command');
        $schedule->onSingleServer();

        $this->assertInstanceOf(SingleServerExtension::class, $schedule->getExtensions()[0]);
    }

    /**
     * @test
     */
    public function can_add_email_on_failure_extension()
    {
        $schedule = new Schedule();
        $schedule->emailOnFailure('kevin@example.com', 'my subject', function (Email $email) {
            $email->cc('emily@example.com');
        });

        $this->assertTrue($schedule->getExtensions()[0]->isHook(Schedule::FAILURE));
        $this->assertSame('kevin@example.com', $schedule->getExtensions()[0]->getEmail()->getTo()[0]->toString());
        $this->assertSame('emily@example.com', $schedule->getExtensions()[0]->getEmail()->getCc()[0]->toString());
        $this->assertSame('my subject', $schedule->getExtensions()[0]->getEmail()->getSubject());
    }

    /**
     * @test
     */
    public function can_add_environment_extension()
    {
        $schedule = new Schedule();
        $schedule->environments('prod', 'stage');

        $this->assertSame(['prod', 'stage'], $schedule->getExtensions()[0]->getRunEnvironments());
    }

    /**
     * @test
     */
    public function can_set_timezone()
    {
        $schedule = new Schedule();
        $schedule->add((new MockTask())->description('task1'));
        $schedule->add((new MockTask())->description('task2')->timezone('America/Toronto'));

        $this->assertNull($schedule->getTimezone());
        $this->assertNull($schedule->all()[0]->getTimezone());
        $this->assertNull($schedule->due(new \DateTime())[0]->getTimezone());
        $this->assertSame('America/Toronto', $schedule->all()[1]->getTimezone()->getName());
        $this->assertSame('America/Toronto', $schedule->due(new \DateTime())[1]->getTimezone()->getName());

        $schedule->timezone('UTC');

        $this->assertSame('UTC', $schedule->getTimezone()->getName());
        $this->assertSame('UTC', $schedule->all()[0]->getTimezone()->getName());
        $this->assertSame('UTC', $schedule->due(new \DateTime())[0]->getTimezone()->getName());
        $this->assertSame('America/Toronto', $schedule->all()[1]->getTimezone()->getName());
        $this->assertSame('America/Toronto', $schedule->due(new \DateTime())[1]->getTimezone()->getName());

        $schedule->timezone(new \DateTimeZone('America/Los_Angeles'));

        $this->assertSame('America/Los_Angeles', $schedule->getTimezone()->getName());
        $this->assertSame('UTC', $schedule->all()[0]->getTimezone()->getName());
        $this->assertSame('UTC', $schedule->due(new \DateTime())[0]->getTimezone()->getName());
        $this->assertSame('America/Toronto', $schedule->all()[1]->getTimezone()->getName());
        $this->assertSame('America/Toronto', $schedule->due(new \DateTime())[1]->getTimezone()->getName());
    }
}
