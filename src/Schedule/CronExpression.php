<?php

namespace Zenstruck\ScheduleBundle\Schedule;

use Cron\CronExpression as CronSchedule;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class CronExpression
{
    public const MINUTE = 0;
    public const HOUR = 1;
    public const DOM = 2;
    public const MONTH = 3;
    public const DOW = 4;

    public const ALIASES = [
        '@hourly',
        '@daily',
        '@weekly',
        '@monthly',
        '@yearly',
        '@annually',
    ];

    private const HASH_ALIAS_MAP = [
        '#hourly' => '# * * * *',
        '#daily' => '# # * * *',
        '#weekly' => '# # * * #',
        '#monthly' => '# # # * *',
        '#annually' => '# # # # *',
        '#yearly' => '# # # # *',
        '#midnight' => '# #(0-2) * * *',
    ];

    private const RANGES = [
        self::MINUTE => [0, 59],
        self::HOUR => [0, 23],
        self::DOM => [1, 28],
        self::MONTH => [1, 12],
        self::DOW => [0, 6],
    ];

    private $value;
    private $parts;
    private $context;
    private $parsedValue;

    public function __construct(string $value, string $context)
    {
        $this->value = $value;
        $this->context = $context;

        if (\in_array($value, self::ALIASES, true)) {
            return;
        }

        $value = self::HASH_ALIAS_MAP[$value] ?? $value;
        $parts = \explode(' ', $value);

        if (5 !== \count($parts)) {
            throw new \InvalidArgumentException("\"{$value}\" is an invalid cron expression.");
        }

        $this->parts = $parts;
    }

    public function __toString(): string
    {
        return $this->getParsedValue();
    }

    public function getRawValue(): string
    {
        return $this->value;
    }

    public function getParsedValue(): string
    {
        if (!$this->parts) {
            return $this->getRawValue();
        }

        return $this->parsedValue ?: $this->parsedValue = \implode(' ', [
            $this->parsePart(self::MINUTE),
            $this->parsePart(self::HOUR),
            $this->parsePart(self::DOM),
            $this->parsePart(self::MONTH),
            $this->parsePart(self::DOW),
        ]);
    }

    public function isHashed(): bool
    {
        return $this->getRawValue() !== $this->getParsedValue();
    }

    public function getNextRun(string $timezone = null): \DateTimeInterface
    {
        return CronSchedule::factory($this->getParsedValue())->getNextRunDate('now', 0, false, $timezone);
    }

    public function isDue(\DateTimeInterface $time, string $timezone = null): bool
    {
        return CronSchedule::factory($this->getParsedValue())->isDue($time, $timezone);
    }

    private function parsePart(int $position): string
    {
        $value = $this->parts[$position];

        if (\preg_match('#^\#(\((\d+)-(\d+)\))?$#', $value, $matches)) {
            $value = $this->hashField(
                $matches[2] ?? self::RANGES[$position][0],
                $matches[3] ?? self::RANGES[$position][1]
            );
        }

        return $value;
    }

    private function hashField(int $start, int $end): string
    {
        $possibleValues = \range($start, $end);

        return $possibleValues[(int) \fmod(\hexdec(\mb_substr(\md5($this->context), 0, 10)), \count($possibleValues))];
    }
}
