<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\FlockStore;
use Symfony\Component\Mime\Email;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Zenstruck\ScheduleBundle\Event\AfterTaskEvent;
use Zenstruck\ScheduleBundle\Event\BeforeScheduleEvent;
use Zenstruck\ScheduleBundle\Event\BeforeTaskEvent;
use Zenstruck\ScheduleBundle\Schedule;
use Zenstruck\ScheduleBundle\Schedule\Exception\SkipTask;
use Zenstruck\ScheduleBundle\Schedule\Extension;
use Zenstruck\ScheduleBundle\Schedule\Task;
use Zenstruck\ScheduleBundle\Schedule\Task\Result;
use Zenstruck\ScheduleBundle\Tests\Fixture\MockTask;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class TaskTest extends TestCase
{
    /**
     * @test
     */
    public function can_set_description()
    {
        $task = self::createTask()->description('my description');

        $this->assertSame('my description', $task->getDescription());
        $this->assertSame('my description', (string) $task);
    }

    /**
     * @test
     */
    public function can_set_timezone()
    {
        $task = self::createTask();

        $this->assertNull($task->getTimezone());

        $task->timezone('UTC');

        $this->assertSame('UTC', $task->getTimezone()->getName());

        $task->timezone(new \DateTimeZone('America/Los_Angeles'));

        $this->assertSame('America/Los_Angeles', $task->getTimezone()->getName());
    }

    /**
     * @test
     */
    public function can_get_next_run()
    {
        $this->assertSame(
            (new \DateTime('1st Jan next year'))->getTimestamp(),
            self::createTask()->yearly()->getNextRun()->getTimestamp()
        );
    }

    /**
     * @test
     */
    public function can_determine_if_due()
    {
        $this->assertTrue(self::createTask()->everyMinute()->isDue());
    }

    /**
     * @test
     * @dataProvider frequencyProvider
     */
    public function can_fluently_create_frequency(callable $createTask, string $expectedExpression)
    {
        $this->assertSame($expectedExpression, (string) $createTask()->getExpression());
    }

    public static function frequencyProvider(): array
    {
        return [
            [function () { return self::createTask()->daily(); }, '0 0 * * *'],
            [function () { return self::createTask()->cron('0 0 * * *')->everyMinute(); }, '* 0 * * *'],
            [function () { return self::createTask()->everyFiveMinutes(); }, '*/5 * * * *'],
            [function () { return self::createTask()->everyTenMinutes(); }, '*/10 * * * *'],
            [function () { return self::createTask()->everyFifteenMinutes(); }, '*/15 * * * *'],
            [function () { return self::createTask()->everyThirtyMinutes(); }, '0,30 * * * *'],
            [function () { return self::createTask()->hourly(); }, '0 * * * *'],
            [function () { return self::createTask()->hourlyAt(6); }, '6 * * * *'],
            [function () { return self::createTask()->at('3'); }, '0 3 * * *'],
            [function () { return self::createTask()->at('3:16'); }, '16 3 * * *'],
            [function () { return self::createTask()->dailyAt('3'); }, '0 3 * * *'],
            [function () { return self::createTask()->twiceDaily(); }, '0 1,13 * * *'],
            [function () { return self::createTask()->twiceDaily(2, 14); }, '0 2,14 * * *'],
            [function () { return self::createTask()->weekdays(); }, '* * * * 1-5'],
            [function () { return self::createTask()->weekdays()->at(2); }, '0 2 * * 1-5'],
            [function () { return self::createTask()->weekends(); }, '* * * * 0,6'],
            [function () { return self::createTask()->mondays(); }, '* * * * 1'],
            [function () { return self::createTask()->tuesdays(); }, '* * * * 2'],
            [function () { return self::createTask()->wednesdays(); }, '* * * * 3'],
            [function () { return self::createTask()->thursdays(); }, '* * * * 4'],
            [function () { return self::createTask()->fridays(); }, '* * * * 5'],
            [function () { return self::createTask()->saturdays(); }, '* * * * 6'],
            [function () { return self::createTask()->sundays(); }, '* * * * 0'],
            [function () { return self::createTask()->days(1, 2, 3); }, '* * * * 1,2,3'],
            [function () { return self::createTask()->weekly(); }, '0 0 * * 0'],
            [function () { return self::createTask()->weekly()->at('3:15'); }, '15 3 * * 0'],
            [function () { return self::createTask()->monthly(); }, '0 0 1 * *'],
            [function () { return self::createTask()->monthlyOn(3); }, '0 0 3 * *'],
            [function () { return self::createTask()->monthlyOn(3, '4:15'); }, '15 4 3 * *'],
            [function () { return self::createTask()->twiceMonthly(); }, '0 0 1,16 * *'],
            [function () { return self::createTask()->twiceMonthly(3, 17); }, '0 0 3,17 * *'],
            [function () { return self::createTask()->twiceMonthly()->at('3:15'); }, '15 3 1,16 * *'],
            [function () { return self::createTask()->quarterly(); }, '0 0 1 1-12/3 *'],
            [function () { return self::createTask()->yearly(); }, '0 0 1 1 *'],
            [function () { return self::createTask('my task')->cron('H 0 * * *'); }, '56 0 * * *'],
            [function () { return self::createTask('my task')->cron('@daily'); }, '56 20 * * *'],
            [function () { return self::createTask('my task')->cron('@midnight'); }, '56 2 * * *'],
            [function () { return self::createTask('my task')->cron('@midnight')->daily(); }, '0 0 * * *'],
        ];
    }

    /**
     * @test
     */
    public function has_unique_id_based_on_description_and_frequency()
    {
        $this->assertSame(self::createTask()->getId(), self::createTask()->getId());
        $this->assertNotSame(self::createTask()->daily()->getId(), self::createTask()->getId());
        $this->assertNotSame(self::createTask('task1')->getId(), self::createTask('task2')->getId());
        $this->assertNotSame((new class('task') extends Task {
        })->getId(), self::createTask('task')->getId());
    }

    /**
     * @test
     */
    public function false_when_filter_skips_task()
    {
        $task = self::createTask();

        $task->when('boolean value', false);

        $this->expectException(SkipTask::class);
        $this->expectExceptionMessage('boolean value');

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));
    }

    /**
     * @test
     */
    public function callback_returning_false_when_filter_skips_task()
    {
        $task = self::createTask();

        $task->when('callback value', function () { return false; });

        $this->expectException(SkipTask::class);
        $this->expectExceptionMessage('callback value');

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));
    }

    /**
     * @test
     */
    public function true_when_filter_allows_task_to_run()
    {
        $task = self::createTask();

        $task->when('boolean value', true);

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function callback_returning_true_when_filter_allows_task_to_run()
    {
        $task = self::createTask();

        $task->when('callback value', function () { return true; });

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function true_skip_filter_skips_task()
    {
        $task = self::createTask();

        $task->skip('boolean value', true);

        $this->expectException(SkipTask::class);
        $this->expectExceptionMessage('boolean value');

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));
    }

    /**
     * @test
     */
    public function callback_returning_true_skip_filter_skips_task()
    {
        $task = self::createTask();

        $task->skip('callback value', function () { return true; });

        $this->expectException(SkipTask::class);
        $this->expectExceptionMessage('callback value');

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));
    }

    /**
     * @test
     */
    public function false_skip_filter_allows_task_to_run()
    {
        $task = self::createTask();

        $task->skip('boolean value', false);

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function callback_returning_false_skip_filter_allows_task_to_run()
    {
        $task = self::createTask();

        $task->skip('callback value', function () { return false; });

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function can_add_callback_extensions()
    {
        $task = self::createTask();
        $calls = [];

        $task->filter(function () use (&$calls) { $calls[] = 'filter'; });
        $task->before(function () use (&$calls) { $calls[] = 'before'; });
        $task->after(function () use (&$calls) { $calls[] = 'after'; });
        $task->then(function () use (&$calls) { $calls[] = 'then'; });
        $task->onSuccess(function () use (&$calls) { $calls[] = 'onSuccess'; });
        $task->onFailure(function () use (&$calls) { $calls[] = 'onFailure'; });

        $task->getExtensions()[0]->filterTask($event = new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));
        $task->getExtensions()[1]->beforeTask($event);
        $task->getExtensions()[2]->afterTask($event = new AfterTaskEvent($event, Result::successful($task)));
        $task->getExtensions()[3]->afterTask($event);
        $task->getExtensions()[4]->onTaskSuccess($event);
        $task->getExtensions()[5]->onTaskFailure($event);

        $this->assertSame([
            'filter',
            'before',
            'after',
            'then',
            'onSuccess',
            'onFailure',
        ], $calls);
    }

    /**
     * @test
     */
    public function can_add_ping_extensions()
    {
        $task = self::createTask();

        $task->pingBefore('http://before.com');
        $task->pingAfter('http://after.com', 'POST');
        $task->thenPing('http://then.com');
        $task->pingOnSuccess('http://success.com');
        $task->pingOnFailure('http://failure.com');

        $client = $this->createMock(HttpClientInterface::class);
        $client->expects($this->exactly(5))->method('request')->withConsecutive(
            [$this->equalTo('GET'), $this->equalTo('http://before.com'), $this->isType('array')],
            [$this->equalTo('POST'), $this->equalTo('http://after.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('http://then.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('http://success.com'), $this->isType('array')],
            [$this->equalTo('GET'), $this->equalTo('http://failure.com'), $this->isType('array')]
        );

        $task->getExtensions()[0]->setHttpClient($client)->beforeTask($event = new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));
        $task->getExtensions()[1]->setHttpClient($client)->afterTask($event = new AfterTaskEvent($event, Result::successful($task)));
        $task->getExtensions()[2]->setHttpClient($client)->afterTask($event);
        $task->getExtensions()[3]->setHttpClient($client)->onTaskSuccess($event);
        $task->getExtensions()[4]->setHttpClient($client)->onTaskFailure($event);
    }

    /**
     * @test
     * @dataProvider emailAfterMethodProvider
     */
    public function can_add_email_after_extension($method)
    {
        $task = self::createTask();
        $task->{$method}('kevin@example.com', 'my subject', function (Email $email) {
            $email->cc('emily@example.com');
        });

        $this->assertTrue($task->getExtensions()[0]->isHook(Extension::TASK_AFTER));
        $this->assertSame('kevin@example.com', $task->getExtensions()[0]->getEmail()->getTo()[0]->toString());
        $this->assertSame('emily@example.com', $task->getExtensions()[0]->getEmail()->getCc()[0]->toString());
        $this->assertSame('my subject', $task->getExtensions()[0]->getEmail()->getSubject());
    }

    public static function emailAfterMethodProvider()
    {
        return [
            ['emailAfter'],
            ['thenEmail'],
        ];
    }

    /**
     * @test
     */
    public function can_add_email_on_failure_extension()
    {
        $task = self::createTask();
        $task->emailOnFailure('kevin@example.com', 'my subject', function (Email $email) {
            $email->cc('emily@example.com');
        });

        $this->assertTrue($task->getExtensions()[0]->isHook(Extension::TASK_FAILURE));
        $this->assertSame('kevin@example.com', $task->getExtensions()[0]->getEmail()->getTo()[0]->toString());
        $this->assertSame('emily@example.com', $task->getExtensions()[0]->getEmail()->getCc()[0]->toString());
        $this->assertSame('my subject', $task->getExtensions()[0]->getEmail()->getSubject());
    }

    /**
     * @test
     */
    public function can_add_single_server_extension()
    {
        $task1 = self::createTask('task')->onSingleServer();
        $task2 = self::createTask('task')->onSingleServer();

        $lockFactory = new LockFactory(new FlockStore());

        $task1->getExtensions()[0]->aquireTaskLock($lockFactory, $task1, \time());

        $this->expectException(SkipTask::class);
        $this->expectExceptionMessage('Task running on another server.');

        $task2->getExtensions()[0]->aquireTaskLock($lockFactory, $task2, \time());
    }

    /**
     * @test
     */
    public function can_add_without_overlapping_extension()
    {
        $task1 = self::createTask('task')->withoutOverlapping();
        $task2 = self::createTask('task')->withoutOverlapping();

        $task1->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task1));

        $this->expectException(SkipTask::class);
        $this->expectExceptionMessage('Task running in another process.');

        $task2->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task2));
    }

    /**
     * @test
     * @dataProvider betweenExtensionSkipProvider
     */
    public function between_extension_skip($start, $end, $inclusive)
    {
        $start = (new \DateTime($start))->format('H:i');
        $end = (new \DateTime($end))->format('H:i');

        $task = self::createTask()->between($start, $end, $inclusive);

        $this->expectException(SkipTask::class);
        $this->expectExceptionMessage("Only runs between {$start} and {$end}");

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));
    }

    public static function betweenExtensionSkipProvider()
    {
        return [
            ['+2 minutes', '+3 minutes', true],
            ['now', '+3 minutes', false],
            ['+5 minutes', '+23 hours', true],
        ];
    }

    /**
     * @test
     * @dataProvider betweenExtensionRunProvider
     */
    public function between_extension_run($start, $end, $inclusive)
    {
        $start = (new \DateTime($start))->format('H:i');
        $end = (new \DateTime($end))->format('H:i');

        $task = self::createTask()->between($start, $end, $inclusive);

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));

        $this->assertTrue(true);
    }

    public static function betweenExtensionRunProvider()
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

        $task = self::createTask()->unlessBetween($start, $end, $inclusive);

        $this->expectException(SkipTask::class);
        $this->expectExceptionMessage("Only runs if not between {$start} and {$end}");

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));
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

        $task = self::createTask()->unlessBetween($start, $end, $inclusive);

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));

        $this->assertTrue(true);
    }

    public static function unlessBetweenExtensionRunProvider()
    {
        return [
            ['now', '+3 minutes', false],
            ['+1 minute', '+3 minutes', true],
            ['+5 minutes', '+23 hours', true],
        ];
    }

    private static function createTask(string $description = 'task description'): Task
    {
        return new MockTask($description);
    }
}
