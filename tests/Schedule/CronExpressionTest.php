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
     */
    public function can_handle_standard_expressions()
    {
        $expressionA = new CronExpression('0 * * * *', 'my task');
        $expressionB = new CronExpression('0 * * * *', 'my task');
        $expressionC = new CronExpression('0 * * * *', 'another task');

        $this->assertFalse($expressionA->isHashed());
        $this->assertSame('0 * * * *', $expressionA->getRawValue());
        $this->assertSame('0 * * * *', $expressionA->getParsedValue());
        $this->assertSame((string) $expressionA, $expressionA->getParsedValue());
        $this->assertSame((string) $expressionA, (string) $expressionB);
        $this->assertSame((string) $expressionA, (string) $expressionC);
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
    }

    public static function hashedExpressionProvider(): array
    {
        return [
            ['H * * * *', '56 * * * *'],
            ['H H * * *', '56 20 * * *'],
            ['H H H * *', '56 20 1 * *'],
            ['H H H H *', '56 20 1 9 *'],
            ['H H H H H', '56 20 1 9 0'],
            ['H H 1,15 1-11 *', '56 20 1,15 1-11 *'],
            ['H H 1,15 * *', '56 20 1,15 * *'],
            ['@hourly', '56 * * * *'],
            ['@daily', '56 20 * * *'],
            ['@weekly', '56 20 * * 0'],
            ['@monthly', '56 20 1 * *'],
            ['@yearly', '56 20 1 9 *'],
            ['@annually', '56 20 1 9 *'],
            ['H(1-15) * * * *', '12 * * * *'],
            ['H(1-15) * * * H(3-5)', '12 * * * 5'],
            ['H(1-15) * H * H(3-5)', '12 * 1 * 5'],
            ['@midnight', '56 2 * * *'],
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
            ['@ daily'],
            ['@everyday'],
        ];
    }
}
