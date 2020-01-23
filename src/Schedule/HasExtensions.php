<?php

namespace Zenstruck\ScheduleBundle\Schedule;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
trait HasExtensions
{
    /** @var Extension[] */
    private $extensions = [];

    final public function addExtension(Extension $extension): self
    {
        $this->extensions[] = $extension;

        return $this;
    }

    /**
     * @return Extension[]
     */
    final public function getExtensions(): array
    {
        return $this->extensions;
    }
}
