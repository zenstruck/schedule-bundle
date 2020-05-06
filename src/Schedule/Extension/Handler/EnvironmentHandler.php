<?php

namespace Zenstruck\ScheduleBundle\Schedule\Extension\Handler;

use Zenstruck\ScheduleBundle\Schedule\Exception\SkipSchedule;
use Zenstruck\ScheduleBundle\Schedule\Extension\EnvironmentExtension;
use Zenstruck\ScheduleBundle\Schedule\Extension\ExtensionHandler;
use Zenstruck\ScheduleBundle\Schedule\ScheduleRunContext;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class EnvironmentHandler extends ExtensionHandler
{
    private $currentEnvironment;

    public function __construct(string $currentEnvironment)
    {
        $this->currentEnvironment = $currentEnvironment;
    }

    /**
     * @param EnvironmentExtension $extension
     */
    public function filterSchedule(ScheduleRunContext $context, object $extension): void
    {
        if (\in_array($this->currentEnvironment, $extension->getRunEnvironments(), true)) {
            return; // currently in configured environment
        }

        throw new SkipSchedule(\sprintf('Schedule configured not to run in [%s] environment (only [%s]).', $this->currentEnvironment, \implode(', ', $extension->getRunEnvironments())));
    }

    public function supports(object $extension): bool
    {
        return $extension instanceof EnvironmentExtension;
    }
}
