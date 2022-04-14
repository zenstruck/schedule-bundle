<?php

namespace Zenstruck\ScheduleBundle\Tests\Schedule\Builder;

use PHPUnit\Framework\TestCase;
use Zenstruck\ScheduleBundle\Schedule\Builder\ScheduledServiceBuilder;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class ScheduledServiceBuilderTest extends TestCase
{
    /**
     * @test
     */
    public function validate_invalid_class(): void
    {
        $this->expectException(\LogicException::class);

        ScheduledServiceBuilder::validate('invalid', []);
    }

    /**
     * @test
     */
    public function validate_missing_frequency(): void
    {
        $this->expectException(\LogicException::class);

        ScheduledServiceBuilder::validate(DummyClass::class, []);
    }

    /**
     * @test
     */
    public function validate_missing_method(): void
    {
        $this->expectException(\LogicException::class);

        ScheduledServiceBuilder::validate(DummyClass::class, ['frequency' => '@daily']);
    }

    /**
     * @test
     */
    public function validate_invalid_method(): void
    {
        $this->expectException(\LogicException::class);

        ScheduledServiceBuilder::validate(DummyClass::class, ['frequency' => '@daily', 'method' => 'invalid']);
    }

    /**
     * @test
     */
    public function validate_static_method(): void
    {
        $this->expectException(\LogicException::class);

        ScheduledServiceBuilder::validate(DummyClass::class, ['frequency' => '@daily', 'method' => 'method1']);
    }

    /**
     * @test
     */
    public function validate_non_public_method(): void
    {
        $this->expectException(\LogicException::class);

        ScheduledServiceBuilder::validate(DummyClass::class, ['frequency' => '@daily', 'method' => 'method3']);
    }

    /**
     * @test
     */
    public function validate_method_required_parameters(): void
    {
        $this->expectException(\LogicException::class);

        ScheduledServiceBuilder::validate(DummyClass::class, ['frequency' => '@daily', 'method' => 'method2']);
    }

    /**
     * @test
     */
    public function validate_arguments(): void
    {
        $this->expectException(\LogicException::class);

        ScheduledServiceBuilder::validate(DummyClass::class, ['frequency' => '@daily', 'method' => '__invoke', 'arguments' => '-v']);
    }
}

class DummyClass
{
    public function __invoke()
    {
    }

    public static function method1()
    {
    }

    public function method2($required)
    {
    }

    private function method3()
    {
    }
}
