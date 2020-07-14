<?php

namespace Zenstruck\ScheduleBundle\Schedule\Task;

/**
 * @author Kevin Bond <kevinbond@gmail.com>
 */
final class Config
{
    private $data = [];

    /**
     * @param mixed $value
     */
    public function set(string $name, $value): self
    {
        $this->data[$name] = $value;

        return $this;
    }

    /**
     * @param mixed $default
     *
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        return $this->data[$name] ?? $default;
    }

    public function all(): array
    {
        return $this->data;
    }

    public function humanized(): array
    {
        $ret = [];

        foreach ($this->data as $key => $value) {
            switch (true) {
                case \is_bool($value):
                    $value = $value ? 'yes' : 'no';

                    break;

                case \is_scalar($value):
                    break;

                case \is_object($value):
                    $value = \sprintf('(%s)', \get_class($value));

                    break;

                default:
                    $value = \sprintf('(%s)', \gettype($value));
            }

            $ret[$key] = $value;
        }

        return $ret;
    }
}
