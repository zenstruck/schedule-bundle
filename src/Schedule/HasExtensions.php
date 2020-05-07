<?php

namespace Zenstruck\ScheduleBundle\Schedule;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait HasExtensions
{
    /** @var object[] */
    private $extensions = [];

    final public function addExtension(object $extension): self
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /**
     * @return object[]
     */
    final public function getExtensions(): array
    {
        return $this->extensions;
    }
}
