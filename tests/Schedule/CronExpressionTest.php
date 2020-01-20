<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\CronExpression;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CronExpressionTest extends TestCase
{
    /**
     * @test
     * @dataProvider standardExpressionProvider
     */
    public function can_handle_standard_expressions($expression)
    {
        $expressionA = new CronExpression($expression, 'my task');
        $expressionB = new CronExpression($expression, 'my task');
        $expressionC = new CronExpression($expression, 'another task');

        $this->assertFalse($expressionA->isHashed());
        $this->assertSame($expression, $expressionA->getRawValue());
        $this->assertSame($expression, $expressionA->getParsedValue());
        $this->assertSame((string) $expressionA, $expressionA->getParsedValue());
        $this->assertSame((string) $expressionA, (string) $expressionB);
        $this->assertSame((string) $expressionA, (string) $expressionC);
        $this->assertInstanceOf(\DateTimeInterface::class, $expressionA->getNextRun());
    }

    public static function standardExpressionProvider(): array
    {
        return [
            ['0 * * * *'],
            ['0 0 * * 1'],
            ['@hourly'],
            ['@daily'],
            ['@weekly'],
            ['@monthly'],
            ['@yearly'],
            ['@annually'],
        ];
    }

    /**
     * @test
     * @dataProvider hashedExpressionProvider
     */
    public function can_handle_hashed_expressions($value, $expected)
    {
        $expressionA = new CronExpression($value, 'my task');
        $expressionB = new CronExpression($value, 'my task');
        $expressionC = new CronExpression($value, 'another task');

        $this->assertTrue($expressionA->isHashed());
        $this->assertSame($value, $expressionA->getRawValue());
        $this->assertSame((string) $expressionA, $expressionA->getParsedValue());
        $this->assertSame((string) $expressionA, (string) $expressionB);
        $this->assertNotSame((string) $expressionA, (string) $expressionC);
        $this->assertSame($expected, (string) $expressionA);
        $this->assertInstanceOf(\DateTimeInterface::class, $expressionA->getNextRun());
    }

    public static function hashedExpressionProvider(): array
    {
        return [
            ['# * * * *', '56 * * * *'],
            ['# # * * *', '56 20 * * *'],
            ['# # # * *', '56 20 1 * *'],
            ['# # # # *', '56 20 1 9 *'],
            ['# # # # #', '56 20 1 9 0'],
            ['# # 1,15 1-11 *', '56 20 1,15 1-11 *'],
            ['# # 1,15 * *', '56 20 1,15 * *'],
            ['#hourly', '56 * * * *'],
            ['#daily', '56 20 * * *'],
            ['#weekly', '56 20 * * 0'],
            ['#monthly', '56 20 1 * *'],
            ['#yearly', '56 20 1 9 *'],
            ['#annually', '56 20 1 9 *'],
            ['#midnight', '56 2 * * *'],
            ['#(1-15) * * * *', '12 * * * *'],
            ['#(1-15) * * * #(3-5)', '12 * * * 5'],
            ['#(1-15) * # * #(3-5)', '12 * 1 * 5'],
        ];
    }

    /**
     * @test
     * @dataProvider invalidExpressionProvider
     */
    public function cannot_set_invalid_cron_expression($value)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("\"{$value}\" is an invalid cron expression.");

        new CronExpression($value, 'context');
    }

    public static function invalidExpressionProvider(): array
    {
        return [
            ['* *'],
            ['*****'],
            ['* * * * * *'],
            ['daily'],
            ['# daily'],
            ['#everyday'],
            ['@ daily'],
        ];
    }
}
