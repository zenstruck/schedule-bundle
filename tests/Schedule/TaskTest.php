<?php

/*
 * This file is part of the zenstruck/schedule-bundle package.
 *
 * (c) Kevin Bond <kevinbond@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Zenstruck\ScheduleBundle\Tests\Schedule;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;
use Zenstruck\ScheduleBundle\Schedule\Extension\SingleServerExtension;
use Zenstruck\ScheduleBundle\Schedule\Task;
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
    }

    /**
     * @test
     */
    public function can_cast_to_string()
    {
        $task = new MockTask('my description');

        $this->assertSame('MockTask: my description', (string) $task);
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
        $this->assertTrue(self::task()->everyMinute()->isDue(new \DateTime()));
    }

    /**
     * @test
     *
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
            [fn() => self::task(), '* * * * *'],
            [fn() => self::task()->minutes(37)->cron('0 0,12 1 */2 *'), '0 0,12 1 */2 *'],
            [fn() => self::task()->weekly()->everyMinute(), '* * * * *'],
            [fn() => self::task()->weekly()->everyFiveMinutes(), '*/5 * * * *'],
            [fn() => self::task()->weekly()->everyTenMinutes(), '*/10 * * * *'],
            [fn() => self::task()->weekly()->everyFifteenMinutes(), '*/15 * * * *'],
            [fn() => self::task()->weekly()->everyTwentyMinutes(), '*/20 * * * *'],
            [fn() => self::task()->weekly()->everyThirtyMinutes(), '0,30 * * * *'],
            [fn() => self::task()->minutes(37)->hourly(), '0 * * * *'],
            [fn() => self::task()->minutes(37)->hourlyAt(2), '2 * * * *'],
            [fn() => self::task()->minutes(37)->hourlyAt(2, 3, '4-5'), '2,3,4-5 * * * *'],
            [fn() => self::task()->minutes(37)->daily(), '0 0 * * *'],
            [fn() => self::task()->minutes(37)->dailyOn(2, 3, '4-5'), '0 2,3,4-5 * * *'],
            [fn() => self::task()->minutes(37)->dailyBetween(9, 17), '0 9-17 * * *'],
            [fn() => self::task()->minutes(37)->twiceDaily(), '0 1,13 * * *'],
            [fn() => self::task()->minutes(37)->twiceDaily(2, 14), '0 2,14 * * *'],
            [fn() => self::task()->minutes(37)->dailyAt(2), '0 2 * * *'],
            [fn() => self::task()->minutes(37)->dailyAt('1:34'), '34 1 * * *'],
            [fn() => self::task()->minutes(37)->weekly(), '0 0 * * 0'],
            [fn() => self::task()->minutes(37)->weeklyOn(2, 3, '4-5'), '0 0 * * 2,3,4-5'],
            [fn() => self::task()->minutes(37)->monthly(), '0 0 1 * *'],
            [fn() => self::task()->minutes(37)->monthlyOn(2, 3, '4-5'), '0 0 2,3,4-5 * *'],
            [fn() => self::task()->minutes(37)->twiceMonthly(), '0 0 1,16 * *'],
            [fn() => self::task()->minutes(37)->twiceMonthly(3, 17), '0 0 3,17 * *'],
            [fn() => self::task()->minutes(37)->quarterly(), '0 0 1 */3 *'],
            [fn() => self::task()->minutes(37)->yearly(), '0 0 1 1 *'],
            [fn() => self::task()->weekly()->minutes(2, 3, '4-5'), '2,3,4-5 0 * * 0'],
            [fn() => self::task()->weekly()->hours(2, 3, '4-5'), '0 2,3,4-5 * * 0'],
            [fn() => self::task()->weekly()->daysOfMonth(2, 3, '4-5'), '0 0 2,3,4-5 * 0'],
            [fn() => self::task()->weekly()->months(2, 3, '4-5'), '0 0 * 2,3,4-5 0'],
            [fn() => self::task()->monthly()->daysOfWeek(2, 3, '4-5'), '0 0 1 * 2,3,4-5'],
            [fn() => self::task()->minutes(37)->weekdays(), '37 * * * 1-5'],
            [fn() => self::task()->minutes(37)->weekends(), '37 * * * 0,6'],
            [fn() => self::task()->minutes(37)->mondays(), '37 * * * 1'],
            [fn() => self::task()->minutes(37)->tuesdays(), '37 * * * 2'],
            [fn() => self::task()->minutes(37)->wednesdays(), '37 * * * 3'],
            [fn() => self::task()->minutes(37)->thursdays(), '37 * * * 4'],
            [fn() => self::task()->minutes(37)->fridays(), '37 * * * 5'],
            [fn() => self::task()->minutes(37)->saturdays(), '37 * * * 6'],
            [fn() => self::task()->minutes(37)->sundays(), '37 * * * 0'],
            [fn() => self::task()->weekly()->at(1), '0 1 * * 0'],
            [fn() => self::task()->weekly()->at('2:45'), '45 2 * * 0'],

            [fn() => self::task()->cron('invalid...')->mondays(), '* * * * 1'],

            [fn() => self::task()->cron('@hourly'), '@hourly'],
            [fn() => self::task()->cron('@daily'), '@daily'],
            [fn() => self::task()->cron('@weekly'), '@weekly'],
            [fn() => self::task()->cron('@monthly'), '@monthly'],
            [fn() => self::task()->cron('@yearly'), '@yearly'],
            [fn() => self::task()->cron('@annually'), '@annually'],

            [fn() => self::task('my task')->cron('# 0 * * *'), '56 0 * * *'],
            [fn() => self::task('my task')->cron('#daily'), '56 20 * * *'],
            [fn() => self::task('my task')->cron('#midnight'), '56 2 * * *'],
            [fn() => self::task('my task')->cron('#midnight')->daily(), '0 0 * * *'],
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
     *
     * @dataProvider emailAfterMethodProvider
     */
    public function can_add_email_after_extension($method)
    {
        $task = self::task();
        $task->{$method}('kevin@example.com', 'my subject', function(Email $email) {
            $email->cc('emily@example.com');
        });

        $this->assertTrue($task->getExtensions()[0]->isHook(Task::AFTER));
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
        $task->emailOnFailure('kevin@example.com', 'my subject', function(Email $email) {
            $email->cc('emily@example.com');
        });

        $this->assertTrue($task->getExtensions()[0]->isHook(Task::FAILURE));
        $this->assertSame('kevin@example.com', $task->getExtensions()[0]->getEmail()->getTo()[0]->toString());
        $this->assertSame('emily@example.com', $task->getExtensions()[0]->getEmail()->getCc()[0]->toString());
        $this->assertSame('my subject', $task->getExtensions()[0]->getEmail()->getSubject());
    }

    /**
     * @test
     */
    public function can_add_single_server_extension()
    {
        $task = self::task()->onSingleServer();

        $this->assertInstanceOf(SingleServerExtension::class, $task->getExtensions()[0]);
    }

    private static function task(string $description = 'task description'): Task
    {
        return new MockTask($description);
    }
}
