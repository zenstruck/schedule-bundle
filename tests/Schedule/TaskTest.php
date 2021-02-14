<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Mime\Email;
use Zenstruck\ScheduleBundle\Schedule;
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
            [function() { return self::task(); }, '* * * * *'],
            [function() { return self::task()->minutes(37)->cron('0 0,12 1 */2 *'); }, '0 0,12 1 */2 *'],
            [function() { return self::task()->weekly()->everyMinute(); }, '* * * * *'],
            [function() { return self::task()->weekly()->everyFiveMinutes(); }, '*/5 * * * *'],
            [function() { return self::task()->weekly()->everyTenMinutes(); }, '*/10 * * * *'],
            [function() { return self::task()->weekly()->everyFifteenMinutes(); }, '*/15 * * * *'],
            [function() { return self::task()->weekly()->everyTwentyMinutes(); }, '*/20 * * * *'],
            [function() { return self::task()->weekly()->everyThirtyMinutes(); }, '0,30 * * * *'],
            [function() { return self::task()->minutes(37)->hourly(); }, '0 * * * *'],
            [function() { return self::task()->minutes(37)->hourlyAt(2); }, '2 * * * *'],
            [function() { return self::task()->minutes(37)->hourlyAt(2, 3, '4-5'); }, '2,3,4-5 * * * *'],
            [function() { return self::task()->minutes(37)->daily(); }, '0 0 * * *'],
            [function() { return self::task()->minutes(37)->dailyOn(2, 3, '4-5'); }, '0 2,3,4-5 * * *'],
            [function() { return self::task()->minutes(37)->dailyBetween(9, 17); }, '0 9-17 * * *'],
            [function() { return self::task()->minutes(37)->twiceDaily(); }, '0 1,13 * * *'],
            [function() { return self::task()->minutes(37)->twiceDaily(2, 14); }, '0 2,14 * * *'],
            [function() { return self::task()->minutes(37)->dailyAt(2); }, '0 2 * * *'],
            [function() { return self::task()->minutes(37)->dailyAt('1:34'); }, '34 1 * * *'],
            [function() { return self::task()->minutes(37)->weekly(); }, '0 0 * * 0'],
            [function() { return self::task()->minutes(37)->weeklyOn(2, 3, '4-5'); }, '0 0 * * 2,3,4-5'],
            [function() { return self::task()->minutes(37)->monthly(); }, '0 0 1 * *'],
            [function() { return self::task()->minutes(37)->monthlyOn(2, 3, '4-5'); }, '0 0 2,3,4-5 * *'],
            [function() { return self::task()->minutes(37)->twiceMonthly(); }, '0 0 1,16 * *'],
            [function() { return self::task()->minutes(37)->twiceMonthly(3, 17); }, '0 0 3,17 * *'],
            [function() { return self::task()->minutes(37)->quarterly(); }, '0 0 1 */3 *'],
            [function() { return self::task()->minutes(37)->yearly(); }, '0 0 1 1 *'],
            [function() { return self::task()->weekly()->minutes(2, 3, '4-5'); }, '2,3,4-5 0 * * 0'],
            [function() { return self::task()->weekly()->hours(2, 3, '4-5'); }, '0 2,3,4-5 * * 0'],
            [function() { return self::task()->weekly()->daysOfMonth(2, 3, '4-5'); }, '0 0 2,3,4-5 * 0'],
            [function() { return self::task()->weekly()->months(2, 3, '4-5'); }, '0 0 * 2,3,4-5 0'],
            [function() { return self::task()->monthly()->daysOfWeek(2, 3, '4-5'); }, '0 0 1 * 2,3,4-5'],
            [function() { return self::task()->minutes(37)->weekdays(); }, '37 * * * 1-5'],
            [function() { return self::task()->minutes(37)->weekends(); }, '37 * * * 0,6'],
            [function() { return self::task()->minutes(37)->mondays(); }, '37 * * * 1'],
            [function() { return self::task()->minutes(37)->tuesdays(); }, '37 * * * 2'],
            [function() { return self::task()->minutes(37)->wednesdays(); }, '37 * * * 3'],
            [function() { return self::task()->minutes(37)->thursdays(); }, '37 * * * 4'],
            [function() { return self::task()->minutes(37)->fridays(); }, '37 * * * 5'],
            [function() { return self::task()->minutes(37)->saturdays(); }, '37 * * * 6'],
            [function() { return self::task()->minutes(37)->sundays(); }, '37 * * * 0'],
            [function() { return self::task()->weekly()->at(1); }, '0 1 * * 0'],
            [function() { return self::task()->weekly()->at('2:45'); }, '45 2 * * 0'],

            [function() { return self::task()->cron('invalid...')->mondays(); }, '* * * * 1'],

            [function() { return self::task()->cron('@hourly'); }, '@hourly'],
            [function() { return self::task()->cron('@daily'); }, '@daily'],
            [function() { return self::task()->cron('@weekly'); }, '@weekly'],
            [function() { return self::task()->cron('@monthly'); }, '@monthly'],
            [function() { return self::task()->cron('@yearly'); }, '@yearly'],
            [function() { return self::task()->cron('@annually'); }, '@annually'],

            [function() { return self::task('my task')->cron('# 0 * * *'); }, '56 0 * * *'],
            [function() { return self::task('my task')->cron('#daily'); }, '56 20 * * *'],
            [function() { return self::task('my task')->cron('#midnight'); }, '56 2 * * *'],
            [function() { return self::task('my task')->cron('#midnight')->daily(); }, '0 0 * * *'],
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

    /**
     * @test
     */
    public function can_add_and_retrieve_task_config(): void
    {
        $task = self::task();
        $task->config()->set('foo', 'bar');
        $task->config()->set('bar', 'baz')->set('baz', 'foo');

        $this->assertSame('bar', $task->config()->get('foo'));
        $this->assertSame('baz', $task->config()->get('bar'));
        $this->assertSame('foo', $task->config()->get('baz'));
        $this->assertNull($task->config()->get('invalid'));
        $this->assertSame('default', $task->config()->get('invalid', 'default'));
        $this->assertSame([
            'foo' => 'bar',
            'bar' => 'baz',
            'baz' => 'foo',
        ], $task->config()->all());
    }

    /**
     * @test
     */
    public function can_get_humanized_config(): void
    {
        $task = self::task();
        $task->config()->set('number', 2);
        $task->config()->set('true', true);
        $task->config()->set('false', false);
        $task->config()->set('array', ['bar']);
        $task->config()->set('object', new Schedule());

        $this->assertSame([
            'number' => 2,
            'true' => 'yes',
            'false' => 'no',
            'array' => '(array)',
            'object' => '('.Schedule::class.')',
        ], $task->config()->humanized());
    }

    private static function task(string $description = 'task description'): Task
    {
        return new MockTask($description);
    }
}
