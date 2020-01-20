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
        $task = self::task()->description('my description');

        $this->assertSame('my description', $task->getDescription());
        $this->assertSame('my description', (string) $task);
    }

    /**
     * @test
     */
    public function can_set_timezone()
    {
        $task = self::task();

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
            self::task()->yearly()->getNextRun()->getTimestamp()
        );
    }

    /**
     * @test
     */
    public function can_determine_if_due()
    {
        $this->assertTrue(self::task()->everyMinute()->isDue());
    }

    /**
     * @test
     * @dataProvider frequencyProvider
     */
    public function can_fluently_create_frequency(callable $createTask, string $expectedExpression)
    {
        $task = $createTask();

        $this->assertSame($expectedExpression, (string) $task->getExpression());
        $this->assertInstanceOf(\DateTimeInterface::class, $task->getNextRun());
    }

    public static function frequencyProvider(): array
    {
        return [
            [function () { return self::task(); }, '* * * * *'],
            [function () { return self::task()->minutes(37)->cron('0 0,12 1 */2 *'); }, '0 0,12 1 */2 *'],
            [function () { return self::task()->weekly()->everyMinute(); }, '* * * * *'],
            [function () { return self::task()->weekly()->everyFiveMinutes(); }, '*/5 * * * *'],
            [function () { return self::task()->weekly()->everyTenMinutes(); }, '*/10 * * * *'],
            [function () { return self::task()->weekly()->everyFifteenMinutes(); }, '*/15 * * * *'],
            [function () { return self::task()->weekly()->everyTwentyMinutes(); }, '*/20 * * * *'],
            [function () { return self::task()->weekly()->everyThirtyMinutes(); }, '0,30 * * * *'],
            [function () { return self::task()->minutes(37)->hourly(); }, '0 * * * *'],
            [function () { return self::task()->minutes(37)->hourlyAt(2); }, '2 * * * *'],
            [function () { return self::task()->minutes(37)->hourlyAt(2, 3, '4-5'); }, '2,3,4-5 * * * *'],
            [function () { return self::task()->minutes(37)->daily(); }, '0 0 * * *'],
            [function () { return self::task()->minutes(37)->dailyOn(2, 3, '4-5'); }, '0 2,3,4-5 * * *'],
            [function () { return self::task()->minutes(37)->twiceDaily(); }, '0 1,13 * * *'],
            [function () { return self::task()->minutes(37)->twiceDaily(2, 14); }, '0 2,14 * * *'],
            [function () { return self::task()->minutes(37)->dailyAt(2); }, '0 2 * * *'],
            [function () { return self::task()->minutes(37)->dailyAt('1:34'); }, '34 1 * * *'],
            [function () { return self::task()->minutes(37)->weekly(); }, '0 0 * * 0'],
            [function () { return self::task()->minutes(37)->weeklyOn(2, 3, '4-5'); }, '0 0 * * 2,3,4-5'],
            [function () { return self::task()->minutes(37)->monthly(); }, '0 0 1 * *'],
            [function () { return self::task()->minutes(37)->monthlyOn(2, 3, '4-5'); }, '0 0 2,3,4-5 * *'],
            [function () { return self::task()->minutes(37)->twiceMonthly(); }, '0 0 1,16 * *'],
            [function () { return self::task()->minutes(37)->twiceMonthly(3, 17); }, '0 0 3,17 * *'],
            [function () { return self::task()->minutes(37)->quarterly(); }, '0 0 1 */3 *'],
            [function () { return self::task()->minutes(37)->yearly(); }, '0 0 1 1 *'],
            [function () { return self::task()->weekly()->minutes(2, 3, '4-5'); }, '2,3,4-5 0 * * 0'],
            [function () { return self::task()->weekly()->hours(2, 3, '4-5'); }, '0 2,3,4-5 * * 0'],
            [function () { return self::task()->weekly()->daysOfMonth(2, 3, '4-5'); }, '0 0 2,3,4-5 * 0'],
            [function () { return self::task()->weekly()->months(2, 3, '4-5'); }, '0 0 * 2,3,4-5 0'],
            [function () { return self::task()->monthly()->daysOfWeek(2, 3, '4-5'); }, '0 0 1 * 2,3,4-5'],
            [function () { return self::task()->minutes(37)->weekdays(); }, '37 * * * 1-5'],
            [function () { return self::task()->minutes(37)->weekends(); }, '37 * * * 0,6'],
            [function () { return self::task()->minutes(37)->mondays(); }, '37 * * * 1'],
            [function () { return self::task()->minutes(37)->tuesdays(); }, '37 * * * 2'],
            [function () { return self::task()->minutes(37)->wednesdays(); }, '37 * * * 3'],
            [function () { return self::task()->minutes(37)->thursdays(); }, '37 * * * 4'],
            [function () { return self::task()->minutes(37)->fridays(); }, '37 * * * 5'],
            [function () { return self::task()->minutes(37)->saturdays(); }, '37 * * * 6'],
            [function () { return self::task()->minutes(37)->sundays(); }, '37 * * * 0'],
            [function () { return self::task()->weekly()->at(1); }, '0 1 * * 0'],
            [function () { return self::task()->weekly()->at('2:45'); }, '45 2 * * 0'],

            [function () { return self::task()->cron('invalid...')->mondays(); }, '* * * * 1'],

            [function () { return self::task('my task')->cron('# 0 * * *'); }, '56 0 * * *'],
            [function () { return self::task('my task')->cron('#daily'); }, '56 20 * * *'],
            [function () { return self::task('my task')->cron('#midnight'); }, '56 2 * * *'],
            [function () { return self::task('my task')->cron('#midnight')->daily(); }, '0 0 * * *'],
        ];
    }

    /**
     * @test
     */
    public function has_unique_id_based_on_description_and_frequency()
    {
        $this->assertSame(self::task()->getId(), self::task()->getId());
        $this->assertNotSame(self::task()->daily()->getId(), self::task()->getId());
        $this->assertNotSame(self::task('task1')->getId(), self::task('task2')->getId());
        $this->assertNotSame((new class('task') extends Task {
        })->getId(), self::task('task')->getId());
    }

    /**
     * @test
     */
    public function false_when_filter_skips_task()
    {
        $task = self::task();

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
        $task = self::task();

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
        $task = self::task();

        $task->when('boolean value', true);

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function callback_returning_true_when_filter_allows_task_to_run()
    {
        $task = self::task();

        $task->when('callback value', function () { return true; });

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function true_skip_filter_skips_task()
    {
        $task = self::task();

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
        $task = self::task();

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
        $task = self::task();

        $task->skip('boolean value', false);

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function callback_returning_false_skip_filter_allows_task_to_run()
    {
        $task = self::task();

        $task->skip('callback value', function () { return false; });

        $task->getExtensions()[0]->filterTask(new BeforeTaskEvent(new BeforeScheduleEvent(new Schedule()), $task));

        $this->assertTrue(true);
    }

    /**
     * @test
     */
    public function can_add_callback_extensions()
    {
        $task = self::task();
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
        $task = self::task();

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
        $task = self::task();
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
        $task = self::task();
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
        $task1 = self::task('task')->onSingleServer();
        $task2 = self::task('task')->onSingleServer();

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
        $task1 = self::task('task')->withoutOverlapping();
        $task2 = self::task('task')->withoutOverlapping();

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

        $task = self::task()->between($start, $end, $inclusive);

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

        $task = self::task()->between($start, $end, $inclusive);

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

        $task = self::task()->unlessBetween($start, $end, $inclusive);

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

        $task = self::task()->unlessBetween($start, $end, $inclusive);

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

    private static function task(string $description = 'task description'): Task
    {
        return new MockTask($description);
    }
}
