<?php

namespace Zenstruck\ScheduleBundle\Tests\Fixture;

use Psr\Log\AbstractLogger;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class MockLogger extends AbstractLogger
{
    private $records = [];

    public function log($level, $message, array $context = []): void
    {
        $this->records[] = $message;
    }

    public function hasMessageThatContains(string $expected): bool
    {
        foreach ($this->records as $record) {
            if (false !== \mb_strpos($record, $expected)) {
                return true;
            }
        }

        return false;
    }
}
