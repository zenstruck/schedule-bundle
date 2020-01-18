<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension;

use Zenstruck\ScheduleBundle\Schedule\Extension;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EnvironmentExtension implements Extension
{
    private $runEnvironments;

    public function __construct(array $runEnvironments)
    {
        if (empty($runEnvironments)) {
            throw new \InvalidArgumentException('At least one environment must be configured.');
        }

        $this->runEnvironments = $runEnvironments;
    }

    public function __toString(): string
    {
        return \sprintf('Only run in [%s] environment%s', \implode(', ', $this->runEnvironments), \count($this->runEnvironments) > 1 ? 's' : '');
    }

    public function getRunEnvironments(): array
    {
        return $this->runEnvironments;
    }
}
